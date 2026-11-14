<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireUnpaidOrders extends Command
{
    protected $signature = 'orders:expire-unpaid';
    protected $description = 'Membatalkan pesanan yang belum dibayar hingga melewati batas waktu kedaluwarsa';

    public function handle()
    {
        // Cari pesanan yang masih PENDING_PAYMENT dan waktu expires_at sudah lewat
        $expiredOrders = Order::where('order_status', 'PENDING') // atau status pending payment
            ->whereHas('payment', function($query) {
                $query->where('payment_status', 'PENDING')
                      ->where('expires_at', '<', now());
            })
            ->get();

        if ($expiredOrders->isEmpty()) {
            $this->info('Tidak ada pesanan kedaluwarsa.');
            return;
        }

        foreach ($expiredOrders as $order) {
            DB::transaction(function () use ($order) {
                $order->order_status = Order::STATUS_CANCELLED;
                $order->save();

                if ($order->payment) {
                    $order->payment->payment_status = 'EXPIRED';
                    $order->payment->transaction_status = 'expire';
                    $order->payment->save();
                }

                // Kembalikan stok produk
                foreach ($order->items as $item) {
                    if ($item->productVariant) {
                        $item->productVariant->increment('stock', $item->quantity);
                    } elseif ($item->product) {
                        $item->product->increment('stock', $item->quantity);
                    }
                }
            });

            $this->info("Pesanan #{$order->id} berhasil dibatalkan karena kedaluwarsa.");
        }
    }
}