<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Payment\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected MidtransService $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
    }

    /**
     * Membuat atau mengambil token pembayaran Snap untuk sebuah order.
     */
    public function generateToken(Request $request, Order $order): JsonResponse
    {
        // 1. Validasi kepemilikan order (opsional jika menggunakan auth sanctum/session)
        // if ($order->user_id !== $request->user()->id) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        // 2. Cek apakah order sudah lunas atau bukan status pending
        if ($order->payment_status === 'PAID') {
            return response()->json([
                'message' => 'Order sudah lunas.',
            ], 409); // 409 Conflict
        }

        if ($order->order_status === Order::STATUS_CANCELLED) {
            return response()->json([
                'message' => 'Order sudah dibatalkan.',
            ], 400);
        }

        try {
            // 3. Panggil service Midtrans untuk membuat transaksi Snap
            $result = $this->midtransService->createSnapTransaction($order);

            return response()->json([
                'status' => 'success',
                'message' => 'Token pembayaran berhasil dibuat.',
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat transaksi Midtrans: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menampilkan halaman pembayaran untuk sebuah order.
     */
    public function show(Order $order)
    {
        // Otorisasi: Pastikan hanya pemilik order yang bisa membuka halaman ini
        $userId = auth()->id();
        $guestOrders = session('guest_orders', []);
        $isOwner = ($userId && $order->user_id === $userId) || in_array($order->id, $guestOrders);

        if (!$isOwner) {
            abort(403, 'Unauthorized action.');
        }

        // Muat relasi item produk dan pembayaran
        $order->load(['items.product', 'payment']);

        return view('payments.show', compact('order'));
    }

    /**
     * Mengembalikan status pembayaran dalam format JSON untuk keperluan polling.
     */
    public function status(Order $order)
    {
        // Otorisasi: Pastikan hanya pemilik order yang bisa mengakses statusnya
        $userId = auth()->id();
        $guestOrders = session('guest_orders', []);
        $isOwner = ($userId && $order->user_id === $userId) || in_array($order->id, $guestOrders);

        if (!$isOwner) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Jika belum lunas, tanya langsung ke Midtrans (tidak bergantung webhook/ngrok)
        if ($order->payment_status !== 'PAID' && $order->payment?->midtrans_order_id) {
            try {
                $this->midtransService->syncFromMidtrans($order);
                $order->refresh();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Sync status Midtrans gagal', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $payment = $order->payment()->first();

        return response()->json([
            'status' => $payment->transaction_status ?? 'pending',
            'order_status' => $order->order_status,
            'payment_status' => $order->payment_status,
            'expires_at' => $payment?->expires_at?->toIso8601String(),
        ]);
    }

    /**
     * Menerima webhook notifikasi dari server Midtrans.
     */
    public function notification(Request $request, MidtransService $midtransService)
    {
        try {
            $midtransService->handleNotification($request->all());
            return response()->json(['status' => 'success', 'message' => 'Notification processed successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * SEMENTARA: diagnosa pembayaran. Hapus setelah masalah selesai.
     */
    public function debug(Order $order)
    {
        $userId = auth()->id();
        $guestOrders = session('guest_orders', []);
        $isOwner = ($userId && $order->user_id === $userId) || in_array($order->id, $guestOrders);

        if (!$isOwner) {
            abort(403);
        }

        $payment = $order->payment;
        $key = (string) config('services.midtrans.server_key');

        $out = [
            'order' => $order->only(['id', 'user_id', 'order_status', 'payment_status', 'paid_at']),
            'payment_row' => $payment?->only(['midtrans_order_id', 'transaction_status', 'payment_status', 'gross_amount']),
            'server_key_dipakai' => substr($key, 0, 14) . '...' . substr($key, -4) . ' (panjang ' . strlen($key) . ')',
            'is_production' => (bool) config('services.midtrans.is_production'),
        ];

        try {
            $out['midtrans_status_api'] = json_decode(
                json_encode(\Midtrans\Transaction::status($payment->midtrans_order_id)),
                true
            );
        } catch (\Throwable $e) {
            $out['midtrans_status_error'] = $e->getMessage();
        }

        return response()->json($out, 200, [], JSON_PRETTY_PRINT);
    }
}