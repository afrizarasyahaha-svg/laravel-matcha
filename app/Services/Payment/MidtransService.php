<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use Midtrans\Config;
use Midtrans\Snap;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    /**
     * Membuat transaksi Snap Midtrans.
     */
    public function createSnapTransaction(Order $order): array
    {
        $order->load(['items.product', 'payment']);

        if ($order->isCashPayment()) {
            throw new \Exception('Pesanan dengan metode tunai tidak diproses melalui Midtrans.');
        }

        if ($order->payment_status === 'PAID') {
            throw new \Exception('Order sudah dibayar.');
        }

        if ($order->order_status === Order::STATUS_CANCELLED) {
            throw new \Exception('Order sudah dibatalkan.');
        }

        // Pakai kembali token yang masih berlaku agar midtrans_order_id tidak
        // berubah-ubah dan transaksi lama tidak menjadi yatim.
        $existingPayment = $order->payment;

        if (
            $existingPayment
            && $existingPayment->snap_token
            && $existingPayment->payment_status === 'PENDING'
            && (! $existingPayment->expires_at || $existingPayment->expires_at->isFuture())
        ) {
            return [
                'token' => $existingPayment->snap_token,
                'redirect_url' => $existingPayment->redirect_url,
            ];
        }

        $expiryMinutes = (int) config('services.midtrans.expiry_minutes', 15);

        // ID dibuat unik per percobaan, tetapi deterministik untuk order ini.
        $midtransOrderId = 'TRX-' . $order->id . '-' . now()->format('YmdHis');

        $itemDetails = [];
        $calculatedTotal = 0;

        foreach ($order->items as $item) {
            $itemName = $item->product->name ?? 'Produk Matcha';

            if ($item->variant) {
                $itemName .= ' - ' . $item->variant->variant_name;
            }

            $itemName = mb_strimwidth($itemName, 0, 50, '');

            $itemPrice = (int) $item->unit_price;
            $quantity = (int) $item->quantity;

            $itemDetails[] = [
                'id' => 'ITEM-' . $item->id,
                'price' => $itemPrice,
                'quantity' => $quantity,
                'name' => $itemName,
            ];

            $calculatedTotal += $itemPrice * $quantity;
        }

        if ($order->shipping_fee > 0) {
            $shippingFee = (int) $order->shipping_fee;

            $itemDetails[] = [
                'id' => 'ONGKIR',
                'price' => $shippingFee,
                'quantity' => 1,
                'name' => 'Biaya Pengiriman (Ongkir)',
            ];

            $calculatedTotal += $shippingFee;
        }

        $grossAmount = $calculatedTotal;

        if ($grossAmount <= 0) {
            throw new \Exception('Total pembayaran tidak valid.');
        }

        $params = [
            'transaction_details' => [
                'order_id' => $midtransOrderId,
                'gross_amount' => $grossAmount,
            ],

            'item_details' => $itemDetails,

            'customer_details' => [
                'first_name' => $order->customer_name,
                'phone' => $order->customer_phone,
            ],

            'expiry' => [
                'start_time' => date('Y-m-d H:i:s O'),
                'unit' => 'minute',
                'duration' => $expiryMinutes,
            ],

            'callbacks' => [
                'finish' => route('payment.show', $order),
            ],
        ];

        // Batasi kanal Snap sesuai metode yang dipilih pelanggan. Untuk data
        // lama (MIDTRANS) tidak dibatasi agar perilaku lama tetap berjalan.
        $enabledPayments = config('services.midtrans.enabled_payments.' . $order->payment_method);

        if (! empty($enabledPayments)) {
            $params['enabled_payments'] = array_values((array) $enabledPayments);
        }

        $snapResponse = Snap::createTransaction($params);

        $order->payment()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'provider' => 'midtrans',
                'midtrans_order_id' => $midtransOrderId,
                'gross_amount' => $grossAmount,
                'snap_token' => $snapResponse->token,
                'redirect_url' => $snapResponse->redirect_url,
                'transaction_status' => 'pending',
                'payment_status' => 'PENDING',
                'expires_at' => now()->addMinutes($expiryMinutes),
                'raw_response' => json_decode(
                    json_encode($snapResponse),
                    true
                ),
            ]
        );

        // Sinkronkan status order dengan baris pembayaran yang baru dibuat.
        if ($order->payment_status !== 'PAID') {
            $order->forceFill([
                'payment_status' => 'PENDING',
                'expires_at' => $order->expires_at ?? now()->addMinutes($expiryMinutes),
            ])->save();
        }

        return [
            'token' => $snapResponse->token,
            'redirect_url' => $snapResponse->redirect_url,
        ];
    }

    /**
     * Memverifikasi signature key dari Midtrans.
     */
    public function verifySignature(array $notification): bool
    {
        $orderId = $notification['order_id'] ?? '';
        $statusCode = $notification['status_code'] ?? '';
        $grossAmount = $notification['gross_amount'] ?? '';
        $incomingSignature = $notification['signature_key'] ?? '';

        $serverKey = config('services.midtrans.server_key');

        if (
            empty($orderId) ||
            empty($statusCode) ||
            empty($grossAmount) ||
            empty($incomingSignature) ||
            empty($serverKey)
        ) {
            return false;
        }

        $mySignature = hash(
            'sha512',
            $orderId . $statusCode . $grossAmount . $serverKey
        );

        return hash_equals($mySignature, $incomingSignature);
    }

    /**
     * Memetakan status transaksi Midtrans.
     */
    public function mapStatus(
        string $transactionStatus,
        ?string $fraudStatus = null
    ): array {
        $orderStatus = Order::STATUS_PENDING;
        $paymentStatus = 'PENDING';

        switch ($transactionStatus) {
            case 'capture':

                if ($fraudStatus === 'accept') {
                    $orderStatus = Order::STATUS_PROCESSING;
                    $paymentStatus = 'PAID';
                } elseif ($fraudStatus === 'challenge') {
                    $orderStatus = Order::STATUS_PENDING;
                    $paymentStatus = 'CHALLENGE';
                }

                break;

            case 'settlement':
                $orderStatus = Order::STATUS_PROCESSING;
                $paymentStatus = 'PAID';
                break;

            case 'pending':
                $orderStatus = Order::STATUS_PENDING;
                $paymentStatus = 'PENDING';
                break;

            case 'deny':
                $orderStatus = Order::STATUS_CANCELLED;
                $paymentStatus = 'DENIED';
                break;

            case 'cancel':
                $orderStatus = Order::STATUS_CANCELLED;
                $paymentStatus = 'CANCELLED';
                break;

            case 'expire':
                $orderStatus = Order::STATUS_CANCELLED;
                $paymentStatus = 'EXPIRED';
                break;

            case 'refund':
                $orderStatus = Order::STATUS_CANCELLED;
                $paymentStatus = 'REFUNDED';
                break;
        }

        return [
            'order_status' => $orderStatus,
            'payment_status' => $paymentStatus,
        ];
    }

    /**
     * Sinkronkan status pembayaran langsung dari Midtrans (Status API),
     * tanpa menunggu webhook. Dipakai oleh polling halaman pembayaran.
     */
    public function syncFromMidtrans(Order $order): void
    {
        $payment = $order->payment;

        if (!$payment || !$payment->midtrans_order_id || $order->payment_status === 'PAID') {
            return;
        }

        $status = json_decode(
            json_encode(\Midtrans\Transaction::status($payment->midtrans_order_id)),
            true
        );

        Log::info('Midtrans Status API response', [
            'midtrans_order_id' => $payment->midtrans_order_id,
            'response' => $status,
        ]);

        if (empty($status['transaction_status'])) {
            Log::warning('Status API tidak mengembalikan transaction_status', [
                'midtrans_order_id' => $payment->midtrans_order_id,
                'status_code' => $status['status_code'] ?? null,
                'status_message' => $status['status_message'] ?? null,
            ]);

            return;
        }

        // Status API tidak menyertakan 'signature_key' pada semua kasus,
        // jadi diproses langsung (data berasal dari server Midtrans, bukan dari klien).
        $this->applyStatus($payment, $status);
    }

    /**
     * Memproses notification webhook dari Midtrans.
     */
    public function handleNotification(array $payload): bool
    {
        Log::info('Midtrans Webhook Received', $payload);

        // 1. Ambil data notification
        $orderId = $payload['order_id'] ?? null;
        $statusCode = $payload['status_code'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;

        // 2. Validasi kelengkapan payload
        if (
            !$orderId ||
            !$statusCode ||
            !$grossAmount ||
            !$transactionStatus ||
            empty($payload['signature_key'])
        ) {
            throw new \Exception(
                'Incomplete Midtrans notification payload.'
            );
        }

        // 3. Verifikasi signature. Jika tidak cocok, jangan langsung percaya payload:
        //    tanyakan status resmi ke Midtrans (Status API) memakai server key kita.
        if (!$this->verifySignature($payload)) {
            Log::warning('Invalid Midtrans signature, verifying via Status API', [
                'order_id' => $orderId,
            ]);

            try {
                $verified = json_decode(json_encode(\Midtrans\Transaction::status($orderId)), true);
            } catch (\Throwable $e) {
                Log::error('Midtrans Status API failed', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage(),
                ]);

                throw new \Exception('Invalid signature key.');
            }

            $transactionStatus = $verified['transaction_status'] ?? null;
            $fraudStatus = $verified['fraud_status'] ?? null;
            $grossAmount = $verified['gross_amount'] ?? $grossAmount;
            $payload = array_merge($payload, $verified ?? []);

            if (!$transactionStatus) {
                throw new \Exception('Invalid signature key.');
            }
        }

        // 4. Cari pembayaran berdasarkan Midtrans order ID
        $payment = Payment::where(
            'midtrans_order_id',
            $orderId
        )->first();

        if (!$payment) {
            Log::warning('Payment transaction not found', [
                'order_id' => $orderId,
            ]);

            throw new \Exception('Payment transaction not found.');
        }

        // 5. Pastikan order tersedia
        if (!$payment->order) {
            throw new \Exception('Order associated with payment not found.');
        }

        // 6-8. Validasi nominal, pemetaan status, dan update database
        $this->applyStatus($payment, array_merge($payload, [
            'transaction_status' => $transactionStatus,
            'fraud_status' => $fraudStatus,
            'gross_amount' => $grossAmount,
        ]));

        return true;
    }

    /**
     * Terapkan status dari Midtrans ke payment & order.
     */
    public function applyStatus(Payment $payment, array $data): void
    {
        $orderId = $payment->midtrans_order_id;
        $transactionStatus = $data['transaction_status'];
        $fraudStatus = $data['fraud_status'] ?? null;
        $grossAmount = $data['gross_amount'] ?? $payment->gross_amount;
        $payload = $data;

        // 6. Validasi nominal pembayaran
        if ((float) $payment->gross_amount !== (float) $grossAmount) {
            throw new \Exception('Gross amount mismatch.');
        }

        // 7. Pemetaan status
        $mappedStatus = $this->mapStatus(
            $transactionStatus,
            $fraudStatus
        );

        // 8. Update database menggunakan transaction dan locking
        DB::transaction(function () use (
            $payment,
            $transactionStatus,
            $mappedStatus,
            $payload
        ) {
            $payment = Payment::where('id', $payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $order = Order::where('id', $payment->order_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Abaikan notifikasi basi: order yang sudah PAID tidak boleh
            // dikembalikan ke status non-PAID oleh notifikasi yang datang terlambat.
            if ($order->payment_status === 'PAID' && $mappedStatus['payment_status'] !== 'PAID') {
                return;
            }

            // Simpan status transaksi Midtrans
            $payment->transaction_status = $transactionStatus;

            // Simpan status pembayaran
            $payment->payment_status = $mappedStatus['payment_status'];

            // Status order tidak boleh turun dari COMPLETED (mis. notifikasi
            // settlement duplikat yang datang setelah admin menyelesaikan order).
            $orderStatus = $mappedStatus['order_status'];

            if (
                $order->order_status === Order::STATUS_COMPLETED
                && $orderStatus !== Order::STATUS_COMPLETED
            ) {
                $orderStatus = Order::STATUS_COMPLETED;
            }

            // Simpan status order
            $order->order_status = $orderStatus;
            $order->payment_status = $mappedStatus['payment_status'];

            // Jika pembayaran berhasil
            if ($mappedStatus['payment_status'] === 'PAID') {
                $payment->paid_at = $payment->paid_at ?? now();
                $order->paid_at = $order->paid_at ?? now();
                $order->expires_at = null;
            }

            // Jika pembayaran dibatalkan atau kedaluwarsa
            if ($mappedStatus['order_status'] === Order::STATUS_CANCELLED) {
                $order->cancelled_at = $order->cancelled_at ?? now();
            }

            // Catatan: stok tidak dikurangi saat checkout, sehingga TIDAK boleh
            // dikembalikan di sini. Blok lama menaikkan stok produk/varian dan
            // justru membuat stok membengkak setiap kali pembayaran gagal.

            // Simpan raw notification
            $payment->raw_response = $payload;

            $payment->save();
            $order->save();
        });

        Log::info('Midtrans notification processed successfully', [
            'order_id' => $orderId,
            'transaction_status' => $transactionStatus,
            'payment_status' => $mappedStatus['payment_status'],
        ]);
    }
}