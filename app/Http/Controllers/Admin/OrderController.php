<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $q = trim((string) $request->query('q'));

        $orders = Order::query()
            ->with(['items.product', 'items.variant'])
            ->when($status && array_key_exists($status, Order::STATUS_LABELS), fn ($query) => $query->where('order_status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('customer_name', 'like', "%{$q}%")
                        ->orWhere('customer_phone', 'like', "%{$q}%")
                        ->orWhere('id', $q);
                });
            })
            ->latest()
            ->get();

        $stats = [
            'total' => Order::count(),
            'processing' => Order::where('order_status', Order::STATUS_PROCESSING)->count(),
            'completed' => Order::where('order_status', Order::STATUS_COMPLETED)->count(),
            'cancelled' => Order::where('order_status', Order::STATUS_CANCELLED)->count(),
        ];

        $statusLabels = Order::STATUS_LABELS;

        return view('admin.orders.index', compact('orders', 'stats', 'status', 'q', 'statusLabels'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', [
                Order::STATUS_PROCESSING,
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELLED,
            ])],
        ], [
            'status.required' => 'Status pesanan wajib dipilih.',
            'status.in' => 'Status pesanan tidak valid.',
        ]);

        $order->update(['order_status' => $data['status']]);

        return back()->with('success', 'Status pesanan '.$order->code.' berhasil diperbarui menjadi '.$order->status_label.'.');
    }
}
