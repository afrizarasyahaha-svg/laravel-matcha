<x-layouts.dashboard :nav="\App\Support\Navigation::konsumen()" role-label="Konsumen"
    title="Pembayaran Pesanan" subtitle="Selesaikan pembayaran pesanan Anda" :show-cart="false">
    
    <div class="card-soft mx-auto max-w-2xl p-6 sm:p-8">
        <!-- Header Status & Nomor Pesanan -->
        <div class="flex justify-between items-center border-b border-matcha-100 pb-4 mb-6">
            <div>
                <h2 class="text-lg font-bold text-matcha-900">Pesanan {{ $order->order_number ?? '#' . $order->id }}</h2>
                <p class="text-xs text-stone-500">Dibuat pada {{ $order->created_at->format('d M Y, H:i') }}</p>
            </div>
            <span id="payment-status-badge" class="px-3 py-1 text-xs font-bold rounded-full 
                {{ $order->payment_status === 'PAID' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                {{ $order->payment_status ?? 'UNPAID' }}
            </span>
        </div>

        <!-- Hitung Mundur Kedaluwarsa -->
        @if($order->payment_status !== 'PAID' && $order->payment && $order->payment->expires_at)
            <div class="mb-6 p-3 bg-amber-50 border border-amber-200 rounded-xl flex justify-between items-center text-xs text-amber-800">
                <span><i class="bi bi-clock mr-1"></i> Batas Waktu Pembayaran:</span>
                <span id="countdown" class="font-bold text-sm">Memuat...</span>
            </div>
        @endif

        <!-- Ringkasan Pesanan -->
        <div class="space-y-3 mb-6">
            @foreach($order->items as $item)
                <div class="flex justify-between text-sm">
                    <span class="text-stone-700">{{ $item->quantity }}× {{ $item->product->name ?? 'Produk' }}</span>
                    <span class="font-semibold text-stone-900">Rp {{ number_format($item->unit_price * $item->quantity, 0, ',', '.') }}</span>
                </div>
            @endforeach

            @if($order->shipping_fee > 0)
                <div class="flex justify-between text-sm border-t border-stone-100 pt-2">
                    <span class="text-stone-700">Ongkir</span>
                    <span class="font-semibold text-stone-900">Rp {{ number_format($order->shipping_fee, 0, ',', '.') }}</span>
                </div>
            @endif

            <div class="flex justify-between text-base font-extrabold border-t border-matcha-100 pt-3 text-matcha-900">
                <span>Total Tagihan</span>
                <span>Rp {{ number_format($order->total_amount ?? $order->subtotal, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Tombol Aksi Pembayaran / Status -->
        <div class="mt-6 text-center" id="action-container">
            @if($order->payment_status === 'PAID')
                <div class="p-4 bg-emerald-50 text-emerald-800 rounded-xl font-medium text-sm">
                    🎉 Pembayaran telah berhasil diselesaikan! Pesanan Anda sedang diproses.
                </div>
                <a href="{{ route('orders.history') }}" class="btn-matcha mt-4 inline-block no-underline">Lihat Riwayat Pesanan</a>
            @else
                @if($order->payment && $order->payment->snap_token)
                    <button id="pay-button" class="btn-matcha w-full py-3 text-base shadow-md">
                        <i class="bi bi-credit-card mr-2"></i> Buka Pembayaran Midtrans
                    </button>
                @else
                    <div class="p-4 bg-amber-50 text-amber-800 rounded-xl text-sm mb-4">
                        Token pembayaran belum digenerate.
                    </div>
                @endif
                <a href="{{ route('orders.history') }}" class="mt-4 block text-sm font-semibold text-matcha-700 hover:underline">Cek Status di Riwayat Pesanan</a>
            @endif
        </div>
    </div>

    <!-- Skrip Midtrans Snap JS SDK & Polling Realtime -->
    @if($order->payment && $order->payment->snap_token)
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
    <script type="text/javascript">
        const orderId = {{ $order->id }};
        let initialStatus = "{{ $order->payment_status }}";

        // 1. Trigger Snap Popup
        document.getElementById('pay-button')?.addEventListener('click', function () {
            window.snap.pay('{{ $order->payment->snap_token }}', {
                onSuccess: function(result) {
                    // Sinkronkan status dari Midtrans dulu, baru reload
                    fetch(`/payments/${orderId}/status`, { headers: { 'Accept': 'application/json' } })
                        .finally(() => location.reload());
                },
                onPending: function(result) {
                    // Biarkan polling yang menangani perubahan
                },
                onClose: function() {
                    // Popup ditutup (mis. setelah bayar lewat simulator): cek status lagi
                    fetch(`/payments/${orderId}/status`, { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json())
                        .then(d => { if (d.payment_status && d.payment_status !== initialStatus) location.reload(); })
                        .catch(() => {});
                },
                onError: function(result) {
                    location.reload();
                }
            });
        });

        // 2. Countdown Timer (Aman dari infinite reload)
        @if($order->payment_status !== 'PAID' && $order->payment && $order->payment->expires_at)
        const expiresAt = new Date("{{ $order->payment->expires_at->toIso8601String() }}").getTime();
        const timerInterval = setInterval(function() {
            const now = new Date().getTime();
            const distance = expiresAt - now;
            const countdownEl = document.getElementById("countdown");

            if (distance < 0) {
                clearInterval(timerInterval);
                if (countdownEl) {
                    countdownEl.textContent = "Waktu habis";
                }
                // Hentikan eksekusi agar tidak reload terus-menerus
                return;
            }

            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            if (countdownEl) {
                countdownEl.textContent = `${minutes}m ${seconds < 10 ? '0' : ''}${seconds}s`;
            }
        }, 1000);
        @endif

        // 3. Polling Status tiap 3 detik (Hanya reload jika status server berubah)
        if (initialStatus !== 'PAID' && initialStatus !== 'EXPIRED' && initialStatus !== 'CANCELLED') {
            const pollInterval = setInterval(async function () {
                try {
                    const response = await fetch(`/payments/${orderId}/status`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) return;

                    const data = await response.json();

                    // Reload HANYA jika status pembayaran di database benar-benar berubah
                    if (data.payment_status && data.payment_status !== initialStatus) {
                        clearInterval(pollInterval);
                        location.reload();
                    }
                } catch (error) {
                    console.error("Gagal melakukan polling status:", error);
                }
            }, 3000);
        }
    </script>
    @endif
</x-layouts.dashboard>