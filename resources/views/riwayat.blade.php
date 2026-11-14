<x-layouts.dashboard :nav="\App\Support\Navigation::konsumen()" role-label="Konsumen"
    title="Riwayat Pesanan" subtitle="Pantau status pesanan yang kamu buat" :show-cart="true">
    @if ($orders->isEmpty())
        <div class="card-soft mx-auto max-w-lg p-10 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-matcha-100 text-2xl text-matcha-700">
                <i class="bi bi-clock-history"></i>
            </div>
            <h2 class="mt-3 text-base font-bold text-matcha-900">Belum ada pesanan</h2>
            <p class="mt-1 text-sm text-stone-500">
                Pesanan yang kamu buat akan muncul di sini{{ auth()->check() ? '.' : ' (tersimpan di perangkat ini, masuk untuk menyimpannya di akun).' }}
            </p>
            <a href="{{ auth()->check() ? route('katalog.index') : route('login') }}" class="btn-matcha mt-4 no-underline">
                {{ auth()->check() ? 'Pilih Menu' : 'Masuk' }}
            </a>
        </div>
    @else
        <div class="mx-auto w-full max-w-4xl space-y-4">
            @guest
                <div class="flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-matcha-200 bg-matcha-50 px-4 py-3 text-xs text-stone-600">
                    <span>
                        <i class="bi bi-info-circle mr-1.5 text-matcha-700"></i>
                        Kamu belum masuk, jadi riwayat ini hanya tersimpan di perangkat/browser ini.
                    </span>
                    <span>
                        <a href="{{ route('login') }}" class="font-semibold text-matcha-700">Masuk</a>
                        atau
                        <a href="{{ route('register') }}" class="font-semibold text-matcha-700">daftar</a>
                        untuk menyimpannya di akun.
                    </span>
                </div>
            @endguest

            @if ($newOrderId)
                <div class="animate-pop-in flex items-center gap-3 rounded-2xl bg-matcha-700 px-4 py-3 text-sm text-white" role="status">
                    <i class="bi bi-check-circle-fill text-lg"></i>
                    <span>Pesanan <b>#MB-{{ str_pad((string) $newOrderId, 5, '0', STR_PAD_LEFT) }}</b> berhasil dibuat. Silakan tunggu konfirmasi dari gerai.</span>
                </div>
            @endif

            @foreach ($orders as $order)
                <div class="card-soft p-5 {{ $order->id === $newOrderId ? 'ring-2 ring-matcha-500' : '' }}">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <div class="text-sm font-bold text-matcha-900">Pesanan {{ $order->code }}</div>
                            <div class="text-xs text-stone-500">
                                {{ $order->created_at?->locale('id')->translatedFormat('d M Y, H:i') }}
                            </div>
                        </div>
                        <x-status-badge :status="$order->order_status" />
                    </div>

                    <ul class="mt-3 divide-y divide-matcha-100/70 text-sm">
                        @foreach ($order->items as $item)
                            <li class="flex justify-between gap-3 py-2">
                                <div>
                                    <div class="font-semibold text-matcha-900">{{ $item->product?->name ?? 'Produk dihapus' }}</div>
                                    <div class="text-xs text-stone-500">
                                        {{ $item->variant?->variant_name ?? 'Tanpa varian' }} · {{ $item->quantity }}x
                                    </div>
                                </div>
                                <div class="shrink-0 font-semibold text-stone-700">
                                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-2 grid gap-4 border-t border-matcha-100 pt-3 text-sm sm:grid-cols-2">
                        <div class="text-xs text-stone-500">
                            <div class="font-semibold text-stone-600">
                                {{ $order->delivery_type === 'ambil' ? 'Ambil sendiri' : 'Diantar' }}
                            </div>
                            <div class="mt-0.5">
                                {{ $order->shipping_address }}
                            </div>
                            <div class="mt-1">{{ $order->customer_name }} · {{ $order->customer_phone }}</div>
                        </div>
                        <dl class="space-y-1">
                            <div class="flex justify-between">
                                <dt class="text-stone-500">Subtotal</dt>
                                <dd>Rp {{ number_format((float) $order->total_amount - (float) $order->shipping_fee, 0, ',', '.') }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-stone-500">Ongkir</dt>
                                <dd>{{ (float) $order->shipping_fee > 0 ? 'Rp '.number_format((float) $order->shipping_fee, 0, ',', '.') : 'Gratis' }}</dd>
                            </div>
                            <div class="flex justify-between border-t border-matcha-100 pt-1 font-bold text-matcha-900">
                                <dt>Total</dt>
                                <dd class="text-matcha-700">Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}</dd>
                            </div>
                            <div class="flex justify-between text-xs text-stone-500">
                                <dt>Jumlah item</dt>
                                <dd>{{ $order->item_count }} item</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Status Pembayaran & Tombol Aksi (Sub-bab 3.2.11) -->
                    <div class="flex flex-wrap items-center justify-between gap-4 mt-4 pt-3 border-t border-stone-100">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-stone-500">Status Pembayaran:</span>
                            <span class="px-2.5 py-1 text-xs font-bold rounded-full 
                                {{ in_array($order->payment_status, ['PAID', 'settlement']) ? 'bg-emerald-100 text-emerald-800' : '' }}
                                {{ in_array($order->payment_status, ['PENDING', 'UNPAID', '']) ? 'bg-amber-100 text-amber-800' : '' }}
                                {{ in_array($order->payment_status, ['EXPIRED', 'CANCELLED', 'deny']) ? 'bg-rose-100 text-rose-800' : '' }}">
                                {{ $order->payment_status ?? 'UNPAID' }}
                            </span>
                        </div>

                        <div>
                            @if(in_array($order->payment_status, ['UNPAID', 'PENDING', '']) && $order->order_status !== 'CANCELLED')
                                <a href="{{ route('payment.show', $order) }}" class="btn-matcha !text-xs !py-1.5 !px-3 no-underline inline-flex items-center">
                                    <i class="bi bi-credit-card mr-1"></i> Bayar Sekarang
                                </a>
                            @else
                                <a href="{{ route('payment.show', $order) }}" class="text-xs font-semibold text-matcha-700 hover:underline inline-flex items-center">
                                    <i class="bi bi-eye mr-1"></i> Detail Pesanan
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.dashboard>