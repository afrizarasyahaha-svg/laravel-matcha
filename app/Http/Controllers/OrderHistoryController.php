<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderHistoryController extends Controller
{
    public function index(Request $request)
    {
        $with = ['items.product', 'items.variant'];

        if ($user = $request->user()) {
            // Konsumen melihat pesanan miliknya sendiri (aturan GET /orders lama).
            $orders = Order::with($with)
                ->where('user_id', $user->id)
                ->latest()
                ->get();
        } else {
            // Tamu: hanya pesanan yang dibuat dari perangkat/session ini.
            $guestOrderIds = session('guest_orders', []);

            $orders = empty($guestOrderIds)
                ? collect()
                : Order::with($with)->whereIn('id', $guestOrderIds)->latest()->get();
        }

        $newOrderId = session('new_order_id');

        return view('riwayat', compact('orders', 'newOrderId'));
    }
}
