<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesReportService
{
    /**
     * Mengambil ringkasan penjualan harian.
     */
    public function daily(Carbon $date): array
    {
        $orders = Order::query()
            ->where('order_status', Order::STATUS_COMPLETED)
            ->whereDate('completed_at', $date->toDateString())
            ->with('items')
            ->get();

        $totalTransactions = $orders->count();

        $totalItemsSold = $orders->sum(function ($order) {
            return $order->items->sum('quantity');
        });

        $totalRevenue = $orders->sum('total_amount');

        $averageTransaction = $totalTransactions > 0
            ? $totalRevenue / $totalTransactions
            : 0;

        return [
            'date' => $date->toDateString(),
            'total_revenue' => $totalRevenue,
            'total_transactions' => $totalTransactions,
            'total_items_sold' => $totalItemsSold,
            'average_transaction' => $averageTransaction,
        ];
    }

    /**
     * Mengambil jumlah produk dan varian yang terjual
     * dalam rentang tanggal tertentu.
     */
    public function productsSold(
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin(
                'product_variants',
                'order_items.product_variant_id',
                '=',
                'product_variants.id'
            )
            ->where('orders.order_status', Order::STATUS_COMPLETED)
            ->whereBetween('orders.completed_at', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'product_variants.id as variant_id',
                'product_variants.variant_name as variant_name',
                DB::raw('SUM(order_items.quantity) as quantity_sold')
            )
            ->groupBy(
                'products.id',
                'products.name',
                'product_variants.id',
                'product_variants.variant_name'
            )
            ->orderByDesc('quantity_sold')
            ->get();
    }

    /**
     * Mengambil ringkasan penjualan berdasarkan jam.
     */
    public function hourly(Carbon $date): Collection
    {
        $sales = DB::table('orders')
            ->where('order_status', Order::STATUS_COMPLETED)
            ->whereDate('completed_at', $date->toDateString())
            ->selectRaw('
                HOUR(completed_at) as hour,
                COUNT(*) as total_transactions,
                SUM(total_amount) as total_revenue
            ')
            ->groupByRaw('HOUR(completed_at)')
            ->get()
            ->keyBy('hour');

        return collect(range(0, 23))->map(function ($hour) use ($sales) {
            $sale = $sales->get($hour);

            return [
                'hour' => $hour,
                'label' => str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00',
                'total_transactions' => (int) ($sale->total_transactions ?? 0),
                'total_revenue' => (float) ($sale->total_revenue ?? 0),
            ];
        });
    }
}
