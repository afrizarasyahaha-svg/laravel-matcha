<x-layouts.dashboard :nav="\App\Support\Navigation::konsumen()" role-label="Konsumen"
    title="Beranda" subtitle="Nikmati kesegaran matcha alami" :show-cart="true">
    <div class="w-full">
        {{-- Banner --}}
        <section class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-matcha-100 to-matcha-200 px-6 py-8 sm:px-10">
            <div class="relative z-10 max-w-md">
                <span class="text-[10px] font-bold tracking-[0.2em] text-matcha-600">MATCHA PREMIUM</span>
                <h2 class="mt-2 text-3xl font-extrabold leading-tight text-matcha-900">
                    Nikmati Keaslian<br>Matcha Alami
                </h2>
                <p class="mt-2 text-sm text-stone-600">Premium grade Uji matcha untuk momen berharga.</p>
                <a href="{{ route('katalog.index') }}" class="btn-matcha mt-5 !py-2 no-underline">Lihat Menu Kami</a>
            </div>
            <div class="pointer-events-none absolute bottom-0 right-6 hidden h-44 w-36 sm:block md:right-16">
                <x-drink-image tone="latte" :rounded="false" alt="Matcha" class="!bg-transparent" />
            </div>
        </section>

        {{-- Paling banyak dicari --}}
        <section class="mt-8">
            <h3 class="text-base font-bold text-matcha-900">Paling Banyak Dicari Bulan Ini 👑</h3>

            @if ($products->isEmpty())
                <p class="mt-4 text-sm text-stone-500">Belum ada produk aktif di server.</p>
            @else
                <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-layouts.dashboard>
