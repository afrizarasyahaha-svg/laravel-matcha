<?php

namespace Database\Seeders;

use App\Models\LowStockAlert;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed data mengikuti skema baru Matcha API.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('payments')->truncate();
        DB::table('order_items')->truncate();
        DB::table('orders')->truncate();
        DB::table('low_stock_alerts')->truncate();
        DB::table('product_variants')->truncate();
        DB::table('products')->truncate();
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // 1. Users
        $users = collect([
            ['name' => 'Admin Matcha', 'email' => 'admin@matcha.com', 'password' => bcrypt('admin123'), 'role' => 'ADMIN'],
            ['name' => 'Achmad Fauzan', 'email' => 'fauzan@gmail.com', 'password' => bcrypt('password123'), 'role' => 'CUSTOMER'],
            ['name' => 'Vanka Rahma', 'email' => 'vanka@gmail.com', 'password' => bcrypt('password123'), 'role' => 'CUSTOMER'],
            ['name' => 'Muhammad Arfian', 'email' => 'arfian@gmail.com', 'password' => bcrypt('password123'), 'role' => 'CUSTOMER'],
            ['name' => 'Siti Aminah', 'email' => 'siti@gmail.com', 'password' => bcrypt('password123'), 'role' => 'CUSTOMER'],
        ])->map(fn (array $data) => User::create($data));

        // 2. Products + variants + low stock alert
        $productsData = [
            [
                'name' => 'Matcha Latte Original',
                'description' => 'Minuman matcha klasik dengan susu segar pilihan berkualitas.',
                'base_price' => 25000,
                'cost_price' => 12000,
                'image_url' => 'https://example.com/matcha-latte.jpg',
                'variants' => [
                    ['variant_name' => 'Cup Reguler (Normal Sugar)', 'additional_price' => 0, 'stock' => 50],
                    ['variant_name' => 'Cup Besar (Less Sugar)', 'additional_price' => 5000, 'stock' => 40],
                ],
                'min_threshold' => 10,
            ],
            [
                'name' => 'Ice Matcha Espresso',
                'description' => 'Perpaduan unik bubuk matcha premium dan shot espresso kuat.',
                'base_price' => 30000,
                'cost_price' => 15000,
                'image_url' => 'https://example.com/matcha-espresso.jpg',
                'variants' => [
                    ['variant_name' => 'Cup Reguler', 'additional_price' => 0, 'stock' => 30],
                    ['variant_name' => 'Cup Besar', 'additional_price' => 6000, 'stock' => 25],
                ],
                'min_threshold' => 15,
            ],
            [
                'name' => 'Matcha Cheese Cream',
                'description' => 'Matcha manis dengan topping krim keju gurih di atasnya.',
                'base_price' => 32000,
                'cost_price' => 16000,
                'image_url' => 'https://example.com/matcha-cheese.jpg',
                'variants' => [
                    ['variant_name' => 'Regular Size', 'additional_price' => 0, 'stock' => 4],
                ],
                'min_threshold' => 10,
            ],
            [
                'name' => 'Matcha Red Bean',
                'description' => 'Matcha autentik disajikan dengan tambahan topping kacang merah.',
                'base_price' => 28000,
                'cost_price' => 13000,
                'image_url' => 'https://example.com/matcha-redbean.jpg',
                'variants' => [
                    ['variant_name' => 'Standard Cup', 'additional_price' => 0, 'stock' => 60],
                ],
                'min_threshold' => 10,
            ],
            [
                'name' => 'Matcha Strawberry Silk',
                'description' => 'Kombinasi menyegarkan antara matcha, susu, dan selai stroberi.',
                'base_price' => 35000,
                'cost_price' => 18000,
                'image_url' => 'https://example.com/matcha-strawberry.jpg',
                'variants' => [
                    ['variant_name' => 'Medium', 'additional_price' => 0, 'stock' => 45],
                    ['variant_name' => 'Large', 'additional_price' => 5000, 'stock' => 35],
                ],
                'min_threshold' => 10,
            ],
        ];

        $createdProducts = collect($productsData)->map(function (array $data) {
            $variants = $data['variants'];
            $minThreshold = $data['min_threshold'];

            $product = Product::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'base_price' => $data['base_price'],
                'cost_price' => $data['cost_price'],
                'image_url' => $data['image_url'],
                'is_active' => true,
            ]);

            $product->variants()->createMany($variants);

            LowStockAlert::create([
                'product_id' => $product->id,
                'min_threshold' => $minThreshold,
                'is_notified' => collect($variants)->contains(fn ($v) => $v['stock'] < $minThreshold),
            ]);

            return $product->load('variants');
        });

        // 3. Orders dummy dengan skema baru & Payment dummy
        $customers = $users->where('role', 'CUSTOMER')->values();

        for ($i = 0; $i < 5; $i++) {
            $customer = $customers[$i % $customers->count()];
            $product = $createdProducts[$i % $createdProducts->count()];
            $variant = $product->variants->first();

            $unitPrice = (float) $product->base_price + (float) $variant->additional_price;
            $quantity = 2;
            $subtotal = $unitPrice * $quantity;
            $shippingFee = ($i % 2 === 0) ? 10000 : 0; // Kombinasi antar / ambil
            $deliveryType = ($shippingFee > 0) ? 'antar' : 'ambil';
            $totalAmount = $subtotal + $shippingFee;

            $isCompleted = ($i === 4);
            $orderStatus = $isCompleted ? Order::STATUS_COMPLETED : Order::STATUS_PROCESSING;
            $paymentStatus = $isCompleted ? 'PAID' : 'UNPAID';

            $order = Order::create([
                'user_id' => $customer->id,
                'order_number' => 'MATCHA-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
                'customer_name' => $customer->name,
                'customer_phone' => '08123456789' . $i,
                'shipping_address' => $deliveryType === 'antar' ? 'Jl. Melati No. ' . ($i + 1) . ', Malang, Jawa Timur' : 'Ambil di tempat (Dine-in)',
                'shipping_fee' => $shippingFee,
                'subtotal' => (int) $subtotal,
                'discount_amount' => 0,
                'total_amount' => $totalAmount,
                'delivery_type' => $deliveryType,
                'order_status' => $orderStatus,
                'payment_status' => $paymentStatus,
                'paid_at' => $isCompleted ? now() : null,
                'completed_at' => $isCompleted ? now() : null,
                'notes' => 'Catatan pesanan ke-' . ($i + 1),
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
                'sweetness' => '50%',
                'note' => 'Less ice',
                'unit_price' => $unitPrice,
            ]);

            // Buat Payment dummy untuk setiap order
            Payment::create([
                'order_id' => $order->id,
                'provider' => 'midtrans',
                'midtrans_order_id' => 'MID-' . strtoupper(Str::random(8)),
                'transaction_id' => $isCompleted ? 'TRX-' . rand(100000, 999999) : null,
                'transaction_status' => $isCompleted ? 'settlement' : 'pending',
                'fraud_status' => $isCompleted ? 'accept' : null,
                'payment_type' => 'gopay',
                'gross_amount' => $totalAmount,
                'snap_token' => 'dummy_snap_token_' . Str::random(10),
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/dummy',
                'raw_response' => json_encode(['status_message' => 'Simulated midtrans response', 'status' => $paymentStatus]),
                'paid_at' => $isCompleted ? now() : null,
            ]);
        }
    }
}