<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\CartService;
use App\Services\Payment\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    private const ONGKIR_FLAT = 10000;

    private const PICKUP_OPTIONS = [
        'Secepatnya (± 15 menit)',
        '30 menit lagi',
        '1 jam lagi',
    ];

    public function index(CartService $cart)
    {
        $rows = $cart->rows();
        $subtotal = $cart->subtotal();
        $ongkir = self::ONGKIR_FLAT;
        $pickupOptions = self::PICKUP_OPTIONS;

        return view('checkout', compact('rows', 'subtotal', 'ongkir', 'pickupOptions'));
    }

    public function store(Request $request, CartService $cart)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'type' => ['required', 'in:antar,ambil'],
            'address' => ['required_if:type,antar', 'nullable', 'string', 'max:500'],
            'pickup' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['required', 'in:'.implode(',', [
                Order::PAYMENT_METHOD_CASH,
                Order::PAYMENT_METHOD_QRIS,
                Order::PAYMENT_METHOD_BANK_TRANSFER,
            ])],
        ], [
            'name.required' => 'Nama penerima wajib diisi.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'address.required_if' => 'Alamat wajib diisi untuk pesanan diantar.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'payment_method.in' => 'Metode pembayaran tidak valid.',
        ]);

        $rows = $cart->rows();

        if (empty($rows)) {
            return back()->with('error', 'Keranjang masih kosong. Pilih menu terlebih dahulu.');
        }

        // Hitung ulang harga di server (tidak mempercayai harga dari session/klien)
        $itemsData = [];
        $subtotal = 0;

        foreach ($rows as $row) {
            $product = Product::with('variants')->find($row['product_id']);

            if (! $product || ! $product->is_active) {
                throw ValidationException::withMessages([
                    'cart' => 'Produk "' . $row['name'] . '" sudah tidak tersedia.',
                ]);
            }

            $variant = null;

            if (! empty($row['variant_id'])) {
                $variant = $product->variants->firstWhere('id', $row['variant_id']);

                if (! $variant) {
                    throw ValidationException::withMessages([
                        'cart' => 'Varian untuk produk "' . $product->name . '" tidak ditemukan.',
                    ]);
                }
            }

            $unitPrice = (float) $product->base_price + ($variant ? (float) $variant->additional_price : 0);
            $quantity = (int) $row['qty'];
            $subtotal += $unitPrice * $quantity;

            // Menyiapkan data item dengan sweetness dan note dari keranjang
            $itemsData[] = [
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'quantity' => $quantity,
                'sweetness' => $row['sweetness'] ?? null,
                'note' => $row['note'] ?? null,
                'unit_price' => $unitPrice,
            ];
        }

        $shippingFee = $data['type'] === 'antar' ? self::ONGKIR_FLAT : 0;
        $totalAmount = $subtotal + $shippingFee;

        $shippingAddress = $data['type'] === 'antar'
            ? $data['address']
            : 'Ambil di tempat (Dine-in/Takeaway)' . (! empty($data['pickup']) ? ' · Ambil: ' . $data['pickup'] : '');

        $isCash = $data['payment_method'] === Order::PAYMENT_METHOD_CASH;

        $order = DB::transaction(function () use ($request, $data, $itemsData, $shippingFee, $subtotal, $totalAmount, $shippingAddress, $isCash) {
            // Generate order number unik, misal: MATCHA-YYYYMMDD-XXXX
            $orderNumber = 'MATCHA-' . date('Ymd') . '-' . strtoupper(Str::random(5));

            $order = Order::create([
                'user_id' => $request->user()?->id,
                'order_number' => $orderNumber,
                'customer_name' => $data['name'],
                'customer_phone' => $data['phone'],
                'shipping_address' => $shippingAddress,
                'shipping_fee' => $shippingFee,
                'subtotal' => (int) $subtotal,
                'discount_amount' => 0,
                'total_amount' => $totalAmount,
                'delivery_type' => $data['type'], // 'antar' atau 'ambil'
                'payment_method' => $data['payment_method'],
                // Tunai langsung diproses di gerai; QRIS/transfer menunggu pembayaran.
                'order_status' => $isCash ? Order::STATUS_PROCESSING : Order::STATUS_PENDING,
                'payment_status' => 'UNPAID',
                'expires_at' => $isCash ? null : now()->addMinutes(config('services.midtrans.expiry_minutes', 15)),
            ]);

            $order->items()->createMany($itemsData);

            return $order;
        });

        // Tamu: simpan id pesanan di session (pengganti localStorage FE lama)
        if (! $request->user()) {
            $guestOrders = session('guest_orders', []);
            $guestOrders[] = $order->id;
            session(['guest_orders' => array_values(array_unique($guestOrders))]);
        }

        $cart->clear();
        session()->flash('new_order_id', $order->id);

        // Pesanan tunai tidak memakai Midtrans: tidak ada token maupun baris
        // pembayaran. Admin menandai lunas setelah uang diterima.
        if ($isCash) {
            return redirect()
                ->route('payment.show', $order)
                ->with('success', 'Pesanan berhasil dibuat. Silakan bayar tunai saat pesanan diambil/diterima.');
        }

        // Buat transaksi Snap Midtrans. Jika gagal (mis. jaringan/konfigurasi),
        // pesanan tetap tersimpan dan pengguna bisa mencoba lagi dari halaman pembayaran.
        try {
            app(MidtransService::class)->createSnapTransaction($order);
        } catch (\Throwable $e) {
            Log::warning('Gagal membuat transaksi Midtrans saat checkout', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('payment.show', $order)
                ->with('error', 'Pesanan berhasil dibuat, tetapi token pembayaran gagal dibuat. Silakan klik "Buat Pembayaran" untuk mencoba lagi.');
        }

        return redirect()
            ->route('payment.show', $order)
            ->with('success', 'Pesanan berhasil dibuat. Silakan selesaikan pembayaran.');
    }
}
