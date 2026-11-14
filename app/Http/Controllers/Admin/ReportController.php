<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\SalesReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function daily(Request $request, SalesReportService $reportService)
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $date = Carbon::parse(
            $validated['date'] ?? today()->toDateString()
        );

        // Ringkasan penjualan harian
        $summary = $reportService->daily($date);

        // Daftar transaksi yang selesai pada tanggal terpilih
        $transactions = Order::query()
            ->where('order_status', Order::STATUS_COMPLETED)
            ->whereDate('completed_at', $date->toDateString())
            ->with(['items.product', 'items.variant'])
            ->latest('completed_at')
            ->paginate(10)
            ->withQueryString();

        // Rekap produk dan varian terjual
        $productsSold = $reportService->productsSold(
            $date->copy()->startOfDay(),
            $date->copy()->endOfDay()
        );

        // Rekap penjualan berdasarkan jam
        $hourlySales = $reportService->hourly($date);

        return view('admin.reports.daily', compact(
            'date',
            'summary',
            'transactions',
            'productsSold',
            'hourlySales'
        ));
    }
}
