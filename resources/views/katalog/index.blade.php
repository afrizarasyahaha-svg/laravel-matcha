<x-layouts.dashboard :nav="\App\Support\Navigation::konsumen()" role-label="Konsumen"
    title="Katalog Menu" subtitle="Pilih minuman favorit dan sesuaikan dengan selera Anda" :show-cart="true">
    <div class="w-full">
        @if ($products->isEmpty())
            <div class="card-soft p-10 text-center">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-matcha-100 text-2xl text-matcha-700">
                    <i class="bi bi-cup-straw"></i>
                </span>
                <h2 class="mt-3 text-base font-bold text-matcha-900">Belum ada menu</h2>
                <p class="mt-1 text-sm text-stone-500">Menu akan tampil di sini setelah admin menambahkannya.</p>
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.dashboard>
