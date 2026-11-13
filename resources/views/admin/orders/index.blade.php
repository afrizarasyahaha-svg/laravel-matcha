<x-layouts.dashboard :nav="\App\Support\Navigation::admin()" role-label="Admin"
    title="Daftar Pesanan" subtitle="Perbarui status pesanan agar pembeli mendapat informasi terbaru">
    <div class="w-full">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Total pesanan" :value="$stats['total']" icon="bi-receipt" />
            <x-stat-card label="Diproses" :value="$stats['processing']" note="Perlu aksi" tone="warn" icon="bi-hourglass-split" />
            <x-stat-card label="Selesai" :value="$stats['completed']" icon="bi-check2-circle" />
            <x-stat-card label="Dibatalkan" :value="$stats['cancelled']" tone="muted" icon="bi-x-circle" />
        </div>

        <div class="card-soft mt-4 p-5">
            <x-section-title title="Pesanan Masuk" desc="Perbarui status pesanan agar pembeli mendapat informasi terbaru." />

            <form method="GET" action="{{ route('admin.orders.index') }}" class="mb-3 flex flex-wrap items-center gap-3">
                <select name="status" class="input-matcha !w-auto !py-1.5 text-sm">
                    <option value="">Semua status</option>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="relative ml-auto">
                    <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-stone-400"></i>
                    <input name="q" value="{{ $q }}" placeholder="Cari nama / telepon / ID..."
                        class="w-56 rounded-xl border border-matcha-200 bg-white py-1.5 pl-8 pr-3 text-sm outline-none transition focus:border-matcha-500">
                </div>
                <button type="submit" class="btn-matcha !py-1.5 text-xs">Terapkan</button>
            </form>

            <div class="overflow-x-auto">
                <table class="table-matcha">
                    <thead>
                        <tr>
                            <th>ID Pesanan</th>
                            <th>Pembeli</th>
                            <th>Jenis</th>
                            <th>Waktu</th>
                            <th>Item</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td class="whitespace-nowrap font-semibold text-matcha-900">{{ $order->code }}</td>
                                <td>
                                    <div class="font-medium text-stone-800">{{ $order->customer_name }}</div>
                                    <div class="text-xs text-stone-500">{{ $order->customer_phone }}</div>
                                    @if ($order->user)
                                        <div class="text-[11px] text-matcha-600">Akun: {{ $order->user->email }}</div>
                                    @else
                                        <div class="text-[11px] text-stone-400">Tamu</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-matcha-800">
                                        <i class="bi {{ $order->delivery_type === 'ambil' ? 'bi-bag-check' : 'bi-bicycle' }}"></i>
                                        {{ $order->delivery_type === 'ambil' ? 'Ambil sendiri' : 'Diantar' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap text-xs">{{ $order->created_at?->format('d M Y, H:i') }}</td>
                                <td>{{ $order->item_count }} menu</td>
                                <td class="whitespace-nowrap">Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}</td>
                                <td><x-status-badge :status="$order->order_status" /></td>
                                <td class="whitespace-nowrap">
                                    <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="flex items-center gap-1.5">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="input-matcha !w-auto !px-2 !py-1 text-xs">
                                            @foreach (['PROCESSING', 'COMPLETED', 'CANCELLED'] as $statusOption)
                                                <option value="{{ $statusOption }}" @selected($order->order_status === $statusOption)>
                                                    {{ $statusLabels[$statusOption] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn-matcha !px-2.5 !py-1 text-xs">Simpan</button>
                                    </form>

                                    <details class="mt-1">
                                        <summary class="cursor-pointer text-xs font-semibold text-matcha-700">Detail</summary>
                                        <ul class="mt-1 space-y-1 rounded-lg bg-matcha-50/70 p-2 text-xs text-stone-600">
                                            @foreach ($order->items as $item)
                                                <li class="flex justify-between gap-2">
                                                    <span>{{ $item->quantity }}× {{ $item->product?->name ?? 'Produk dihapus' }}
                                                        <span class="text-stone-400">({{ $item->variant?->variant_name ?? 'tanpa varian' }})</span>
                                                    </span>
                                                    <span class="shrink-0">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                                </li>
                                            @endforeach
                                            <li class="border-t border-matcha-100 pt-1 text-stone-500">{{ $order->shipping_address }}</li>
                                        </ul>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-stone-400">Tidak ada pesanan yang cocok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.dashboard>
