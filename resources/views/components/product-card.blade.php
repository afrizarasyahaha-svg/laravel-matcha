@props(['product', 'selected' => false])

@php
    $product = $product->loadMissing('variants');
@endphp

<div class="card-soft group overflow-hidden p-2.5 transition-all duration-700 ease-[cubic-bezier(.16,1,.3,1)] hover:-translate-y-1.5 hover:shadow-lift {{ $selected ? 'ring-2 ring-matcha-600' : '' }}">
    <a href="{{ route('katalog.show', $product) }}" class="block">
        <div class="aspect-[4/3] overflow-hidden rounded-xl transition-transform duration-[1200ms] ease-[cubic-bezier(.16,1,.3,1)] group-hover:scale-[1.05]">
            <x-drink-image :src="$product->image" :tone="$product->tone" :alt="$product->name" />
        </div>
    </a>
    <div class="flex items-end justify-between gap-2 px-1 pb-1 pt-3">
        <div class="min-w-0">
            <a href="{{ route('katalog.show', $product) }}" class="block truncate text-sm font-semibold text-matcha-900 no-underline hover:text-matcha-700">
                {{ $product->name }}
            </a>
            <div class="mt-0.5 text-sm font-bold text-matcha-700">Rp {{ number_format((float) $product->base_price, 0, ',', '.') }}</div>
            <div class="text-[11px] text-stone-400">{{ $product->tag }}</div>
        </div>
        <a href="{{ route('katalog.show', $product) }}" aria-label="Tambah {{ $product->name }}"
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-matcha-700 text-white no-underline transition-all duration-300 ease-out hover:rotate-90 hover:scale-110 hover:bg-matcha-800 active:scale-95">
            <i class="bi bi-plus-lg text-sm"></i>
        </a>
    </div>
</div>
