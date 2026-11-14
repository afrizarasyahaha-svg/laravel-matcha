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

        // 2. Menu Matcha and Me (14 menu) + varian ukuran + low stock alert
        // Foto diambil otomatis dari public/images/menu sesuai nama menu (image_url dikosongkan).
        $menu = [
            ['Original Usucha', 10000, 'Original', 'latte', 'Americano-nya matcha. Pahit alami karena hanya pure matcha dan air. Tanpa gula dan susu.'],
            ['Signature Authentic Matcha Latte', 15000, 'Signature', 'latte', 'Pure matcha di-cold whisk pakai susu dan gula (optional). Creamy, manis, umami dengan hint pahit.'],
            ['Signature Cheezy Matcha Latte', 18000, 'Signature', 'milk', 'Matcha latte dengan signature cheezy cream.'],
            ['Coffe Dirty Matcha', 17000, 'Coffee & Matcha', 'choco', 'Matcha latte ditabrak dengan coffee-shot blend Arabica dan Robusta khas Mandailing.'],
            ['Creamy Pink Matcha', 18000, 'Creamy', 'berry', 'Matcha latte dengan pink cream bunga Sakura yang floral aromatic.'],
            ['Creamy Ubee Matcha', 18000, 'Creamy', 'latte', 'Matcha latte dengan manis dan creamy-nya ubee.'],
            ['Creamy Choco Matcha', 18000, 'Creamy', 'choco', 'Matcha latte dengan rich chocolate.'],
            ['Refreshing Ichigo Matcha', 18000, 'Refreshing', 'berry', 'Matcha latte dengan selai buah strawberry asli yang asem, manis dan segar.'],
            ['Refreshing Pistacio Matcha', 18000, 'Refreshing', 'milk', 'Perpaduan creamy pistachio dan matcha autentik, menghadirkan rasa yang lembut, nutty, dan berkelas.'],
            ['Non Matcha Strawberry Milk', 15000, 'Non Matcha', 'berry', 'Strawberry Milk dibuat dari selai stroberi pilihan yang menghasilkan rasa creamy dan menyegarkan.'],
            ['Non Matcha Ubee Milk', 13000, 'Non Matcha', 'berry', 'Ube Milk dengan cita rasa ubi ungu yang khas, creamy, manis, dan lembut di setiap tegukan.'],
            ['Non Matcha Coklat Milk', 13000, 'Non Matcha', 'choco', 'Perpaduan cokelat dan susu yang creamy, dengan sensasi cokelat yang hangat dan akrab.'],
            ['Non Matcha Kopi Gula Aren', 10000, 'Non Matcha', 'choco', 'Coffee dengan sentuhan gula aren. Manisnya lembut, pahitnya seimbang, dengan aroma caramel yang bikin nagih.'],
            ['Special Series Brown Sugar Matcha', 17000, 'Special Series', 'choco', 'Perpaduan gula aren dan matcha autentik, menghadirkan rasa yang lembut dan berkelas.'],
        ];

        // Ukuran minuman (sama dengan FE): S +0, M +3.000, L +6.000
        $sizes = [
            ['variant_name' => 'Size S', 'additional_price' => 0, 'stock' => 50],
            ['variant_name' => 'Size M', 'additional_price' => 3000, 'stock' => 40],
            ['variant_name' => 'Size L', 'additional_price' => 6000, 'stock' => 30],
        ];
        $minThreshold = 10;

        $createdProducts = collect($menu)->map(function (array $row) use ($sizes, $minThreshold) {
            [$name, $price, $tag, $tone, $description] = $row;

            $product = Product::create([
                'name' => $name,
                'description' => $description,
                'base_price' => $price,
                'cost_price' => 0, // isi harga modal lewat Admin > Produk > Edit
                'image_url' => null,
                'is_active' => true,
            ]);

            // tag & tone di luar $fillable model, jadi diisi lewat forceFill
            $product->forceFill(['tag' => $tag, 'tone' => $tone])->save();

            $product->variants()->createMany($sizes);

            LowStockAlert::create([
                'product_id' => $product->id,
                'min_threshold' => $minThreshold,
                'is_notified' => collect($sizes)->contains(fn ($v) => $v['stock'] < $minThreshold),
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
