<?php

namespace Database\Seeders;

use App\Models\LowStockAlert;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed data mengikuti prisma/seed.js dari backend lama.
     * Tabel yang di luar cakupan (payment, expense, notifikasi) tidak diisi.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('order_items')->truncate();
        DB::table('orders')->truncate();
        DB::table('low_stock_alerts')->truncate();
        DB::table('product_variants')->truncate();
        DB::table('products')->truncate();
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // 1. Users
        $users = collect([
            ['name' => 'Admin Matcha', 'email' => 'admin@matcha.com', 'password' => 'admin123', 'role' => 'ADMIN'],
            ['name' => 'Achmad Fauzan', 'email' => 'fauzan@gmail.com', 'password' => 'password123', 'role' => 'CUSTOMER'],
            ['name' => 'Vanka Rahma', 'email' => 'vanka@gmail.com', 'password' => 'password123', 'role' => 'CUSTOMER'],
            ['name' => 'Muhammad Arfian', 'email' => 'arfian@gmail.com', 'password' => 'password123', 'role' => 'CUSTOMER'],
            ['name' => 'Siti Aminah', 'email' => 'siti@gmail.com', 'password' => 'password123', 'role' => 'CUSTOMER'],
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

        // 3. Orders dummy (tanpa Payment, karena Midtrans di luar cakupan)
        $customers = $users->where('role', 'CUSTOMER')->values();

        for ($i = 0; $i < 5; $i++) {
            $customer = $customers[$i % $customers->count()];
            $product = $createdProducts[$i % $createdProducts->count()];
            $variant = $product->variants->first();

            $unitPrice = (float) $product->base_price + (float) $variant->additional_price;
            $quantity = 2;
            $shippingFee = 10000;
            $totalAmount = ($unitPrice * $quantity) + $shippingFee;

            $order = Order::create([
                'user_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => '08123456789'.$i,
                'shipping_address' => 'Jl. Melati No. '.($i + 1).', Malang, Jawa Timur',
                'shipping_fee' => $shippingFee,
                'total_amount' => $totalAmount,
                'order_status' => $i === 4 ? 'COMPLETED' : 'PROCESSING',
            ]);

            $order->items()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
            ]);
        }
    }
}
