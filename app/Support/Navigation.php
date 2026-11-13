<?php

namespace App\Support;

class Navigation
{
    public static function konsumen(): array
    {
        return [
            ['label' => 'Beranda', 'icon' => 'bi-house', 'url' => route('beranda'), 'active' => 'beranda'],
            ['label' => 'Katalog Menu', 'icon' => 'bi-cup-straw', 'url' => route('katalog.index'), 'active' => 'katalog.*'],
            ['label' => 'Keranjang', 'icon' => 'bi-bag', 'url' => route('cart.index'), 'active' => 'cart.*'],
            ['label' => 'Checkout', 'icon' => 'bi-credit-card', 'url' => route('checkout.index'), 'active' => 'checkout.*'],
            ['label' => 'Riwayat Pesanan', 'icon' => 'bi-clock-history', 'url' => route('orders.history'), 'active' => 'orders.history'],
        ];
    }

    public static function admin(): array
    {
        return [
            ['label' => 'Dashboard', 'icon' => 'bi-grid', 'url' => route('admin.dashboard'), 'active' => 'admin.dashboard'],
            ['label' => 'Produk & Menu', 'icon' => 'bi-box-seam', 'url' => route('admin.products.index'), 'active' => 'admin.products.*'],
            ['label' => 'Stok & Persediaan', 'icon' => 'bi-clipboard-data', 'url' => route('admin.stock.index'), 'active' => 'admin.stock.*'],
            ['label' => 'Pesanan', 'icon' => 'bi-receipt', 'url' => route('admin.orders.index'), 'active' => 'admin.orders.*'],
        ];
    }
}
