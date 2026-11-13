<x-layouts.dashboard :nav="\App\Support\Navigation::konsumen()" role-label="Konsumen"
    title="Keranjang Belanja" subtitle="Periksa pilihan menu sebelum melanjutkan pembayaran" :show-cart="true">
    @if (empty($rows))
        <div class="card-soft mx-auto max-w-lg p-10 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-matcha-100 text-2xl text-matcha-700">
                <i class="bi bi-bag"></i>
            </div>
            <h2 class="mt-3 text-base font-bold text-matcha-900">Keranjang masih kosong</h2>
            <p class="mt-1 text-sm text-stone-500">Yuk pilih menu matcha favoritmu terlebih dahulu.</p>
            <a href="{{ route('katalog.index') }}" class="btn-matcha mt-4 no-underline">Pilih Menu</a>
        </div>
    @else
        <div class="grid gap-4 lg:grid-cols-3">
            <div class="card-soft p-5 sm:p-6 lg:col-span-2">
                <h2 class="text-base font-bold text-matcha-900">Keranjang Saya</h2>
                <p class="text-xs text-stone-500">Ubah jumlah atau hapus menu sebelum melanjutkan pembayaran.</p>

                <ul class="mt-4 divide-y divide-matcha-100/80">
                    @foreach ($rows as $row)
                        <li class="flex gap-3 py-4">
                            <div class="h-16 w-16 shrink-0">
                                <x-drink-image :src="$row['image']" :tone="$row['tone']" :alt="$row['name']" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold text-matcha-900">{{ $row['name'] }}</div>
                                        <div class="text-xs text-stone-500">
                                            {{ $row['variant_name'] ?? 'Tanpa varian' }} · gula {{ $row['sweet'] }}
                                            @if (! empty($row['note']))
                                                · "{{ $row['note'] }}"
                                            @endif
                                        </div>
                                        <div class="mt-1 text-sm font-bold text-matcha-700">
                                            Rp {{ number_format((float) $row['unit_price'], 0, ',', '.') }}
                                        </div>
                                    </div>
                                    <div class="text-sm font-bold text-matcha-900">
                                        Rp {{ number_format((float) $row['subtotal'], 0, ',', '.') }}
                                    </div>
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <form method="POST" action="{{ route('cart.update', $row['row_id']) }}" class="flex items-center gap-1.5">
                                        @csrf
                                        @method('PATCH')
                                        <input type="number" name="qty" min="1" max="99" value="{{ $row['qty'] }}"
                                            class="input-matcha !w-20 !px-2 !py-1 text-center text-sm" aria-label="Jumlah {{ $row['name'] }}">
                                        <button type="submit" class="btn-outline-matcha !rounded-lg !px-2.5 !py-1 text-xs">Ubah</button>
                                    </form>
                                    <form method="POST" action="{{ route('cart.remove', $row['row_id']) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-sm-matcha text-red-600 hover:bg-red-50">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <a href="{{ route('katalog.index') }}" class="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-matcha-700 no-underline hover:text-matcha-900">
                    <i class="bi bi-plus-lg"></i> Tambah menu lain
                </a>
            </div>

            <div class="card-soft h-fit p-5">
                <h3 class="text-sm font-bold text-matcha-900">Ringkasan</h3>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-stone-500">{{ collect($rows)->sum('qty') }} item pesanan</dt>
                        <dd class="font-semibold">Rp {{ number_format($subtotal, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-stone-500">Ongkir</dt>
                        <dd class="text-xs text-stone-500">Dihitung saat checkout</dd>
                    </div>
                </dl>
                <div class="mt-4 flex items-center justify-between border-t border-matcha-100 pt-4">
                    <span class="text-sm font-semibold text-matcha-900">Subtotal</span>
                    <span class="text-lg font-extrabold text-matcha-700">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                <a href="{{ route('checkout.index') }}" class="btn-matcha mt-4 w-full no-underline">
                    Lanjut ke Checkout <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    @endif
</x-layouts.dashboard>
