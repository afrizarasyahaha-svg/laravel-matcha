<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        ], [
            'name.required' => 'Nama penerima wajib diisi.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'address.required_if' => 'Alamat wajib diisi untuk pesanan diantar.',
        ]);

        $rows = $cart->rows();

        if (empty($rows)) {
            return back()->with('error', 'Keranjang masih kosong. Pilih menu terlebih dahulu.');
        }

        // Hitung ulang harga di server (tidak mempercayai harga dari session/klien),
        // mengikuti logika orderController.createOrder di backend lama.
        $itemsData = [];
        $subtotal = 0.0;

        foreach ($rows as $row) {
            $product = Product::with('variants')->find($row['product_id']);

            if (! $product || ! $product->is_active) {
                throw ValidationException::withMessages([
                    'cart' => 'Produk "'.$row['name'].'" sudah tidak tersedia.',
                ]);
            }

            $variant = null;

            if (! empty($row['variant_id'])) {
                $variant = $product->variants->firstWhere('id', $row['variant_id']);

                if (! $variant) {
                    throw ValidationException::withMessages([
                        'cart' => 'Varian untuk produk "'.$product->name.'" tidak ditemukan.',
                    ]);
                }
            }

            $unitPrice = (float) $product->base_price + ($variant ? (float) $variant->additional_price : 0);
            $quantity = (int) $row['qty'];
            $subtotal += $unitPrice * $quantity;

            $itemsData[] = [
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
            ];
        }

        $shippingFee = $data['type'] === 'antar' ? self::ONGKIR_FLAT : 0.0;
        $totalAmount = $subtotal + $shippingFee;

        $shippingAddress = $data['type'] === 'antar'
            ? $data['address']
            : 'Ambil di tempat (Dine-in/Takeaway)'.(! empty($data['pickup']) ? ' · Ambil: '.$data['pickup'] : '');

        $order = DB::transaction(function () use ($request, $data, $itemsData, $shippingFee, $totalAmount, $shippingAddress) {
            $order = Order::create([
                'user_id' => $request->user()?->id,
                'customer_name' => $data['name'],
                'customer_phone' => $data['phone'],
                'shipping_address' => $shippingAddress,
                'shipping_fee' => $shippingFee,
                'total_amount' => $totalAmount,
                'order_status' => Order::STATUS_PROCESSING,
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

        return redirect()
            ->route('orders.history')
            ->with('success', 'Pesanan berhasil dibuat. Silakan tunggu konfirmasi dari gerai.');
    }
}
