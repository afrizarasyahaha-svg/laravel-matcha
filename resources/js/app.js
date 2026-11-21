import './bootstrap';

/* ==========================================================================
   Interaksi halaman (pengganti js/ui.js, js/shell.js, dan event handler FE).
   Semua memakai atribut data-*, jadi Blade tinggal menaruh atributnya.
   ========================================================================== */

// Elemen .reveal muncul halus saat masuk layar
function revealAll() {
    const els = document.querySelectorAll('.reveal:not(.is-visible)');
    if (typeof IntersectionObserver === 'undefined') {
        els.forEach((el) => el.classList.add('is-visible'));
        return;
    }
    const io = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.15 },
    );
    els.forEach((el) => io.observe(el));
}

document.addEventListener('DOMContentLoaded', revealAll);

document.addEventListener('click', (e) => {
    // Drawer sidebar mobile
    if (e.target.closest('[data-drawer-open]')) document.getElementById('drawer')?.classList.remove('hidden');
    if (e.target.closest('[data-drawer-close]')) document.getElementById('drawer')?.classList.add('hidden');

    // Grup menu sidebar (dropdown)
    const toggle = e.target.closest('[data-group-toggle]');
    if (toggle) {
        const key = toggle.dataset.groupToggle;
        const open = toggle.getAttribute('aria-expanded') !== 'true';
        document.querySelectorAll(`[data-group="${key}"]`).forEach((g) => {
            g.querySelector('[data-group-toggle]').setAttribute('aria-expanded', open);
            g.querySelector('[data-chevron]').classList.toggle('rotate-180', open);
            const panel = g.querySelector('[data-panel]');
            panel.classList.toggle('grid-rows-[1fr]', open);
            panel.classList.toggle('opacity-100', open);
            panel.classList.toggle('grid-rows-[0fr]', !open);
            panel.classList.toggle('opacity-0', !open);
        });
    }

    // Slider produk di landing
    const slide = e.target.closest('[data-slider]');
    if (slide) {
        document.getElementById('produk-slider')?.scrollBy({ left: Number(slide.dataset.slider) * 280, behavior: 'smooth' });
    }

    // Scroll halus ke bagian di landing
    const scroll = e.target.closest('[data-scroll-to]');
    if (scroll) {
        e.preventDefault();
        document.getElementById(scroll.dataset.scrollTo)?.scrollIntoView({ behavior: 'smooth' });
    }

    // Tampil/sembunyi sandi
    const pw = e.target.closest('[data-toggle-password]');
    if (pw) {
        const input = document.getElementById(pw.dataset.togglePassword);
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        pw.setAttribute('aria-label', show ? 'Sembunyikan sandi' : 'Tampilkan sandi');
        pw.querySelector('i').className = 'bi ' + (show ? 'bi-eye-slash' : 'bi-eye');
    }

    // Stepper jumlah di keranjang (kirim form PATCH)
    const step = e.target.closest('[data-qty-step]');
    if (step) {
        const form = step.closest('form');
        const input = form.querySelector('input[name="qty"]');
        const max = Number(step.dataset.qtyMax || 99);
        const next = Math.min(max, Math.max(1, Number(input.value) + Number(step.dataset.qtyStep)));
        if (next !== Number(input.value)) {
            input.value = next;
            form.submit();
        }
    }

    // Modal <dialog>
    const open = e.target.closest('[data-open-dialog]');
    if (open) document.getElementById(open.dataset.openDialog)?.showModal();
    const close = e.target.closest('[data-close-dialog]');
    if (close) close.closest('dialog')?.close();
    if (e.target instanceof HTMLDialogElement && e.target.classList.contains('modal-matcha')) e.target.close();
});

// Konfirmasi sebelum submit (data-confirm="pesan")
document.addEventListener('submit', (e) => {
    const msg = e.target.dataset?.confirm;
    if (msg && !window.confirm(msg)) e.preventDefault();
});

// Katalog: pilih produk, atur varian, hitung harga
function initKatalog() {
    const root = document.getElementById('katalog');
    if (!root) return;
    const money = (v) => 'Rp ' + Math.round(v).toLocaleString('id-ID');
    const cards = root.querySelectorAll('[data-select-product]');
    const panels = root.querySelectorAll('[data-product-panel]');

    const select = (id) => {
        cards.forEach((c) => c.classList.toggle('ring-2', c.dataset.selectProduct === String(id)));
        cards.forEach((c) => c.classList.toggle('ring-matcha-600', c.dataset.selectProduct === String(id)));
        panels.forEach((p) => {
            const on = p.dataset.productPanel === String(id);
            p.hidden = !on;
            if (on) recalc(p);
        });
    };

    const recalc = (panel) => {
        const base = Number(panel.dataset.base);
        const checked = panel.querySelector('input[name="product_variant_id"]:checked');
        const add = checked ? Number(checked.dataset.add) : 0;
        panel.querySelector('[data-total]').textContent = money(base + add);
    };

    cards.forEach((c) =>
        c.addEventListener('click', (e) => {
            if (e.target.closest('a')) return;
            select(c.dataset.selectProduct);
        }),
    );
    panels.forEach((p) => p.addEventListener('change', () => recalc(p)));

    const wanted = new URLSearchParams(location.search).get('produk');
    const first = root.querySelector('[data-select-product]');
    select(wanted && root.querySelector(`[data-select-product="${wanted}"]`) ? wanted : first?.dataset.selectProduct);
}

// Admin produk: filter kategori
function initFilterKategori() {
    const sel = document.getElementById('filter-kategori');
    if (!sel) return;
    sel.addEventListener('change', () => {
        document.querySelectorAll('[data-kategori]').forEach((tr) => {
            tr.hidden = sel.value !== '' && tr.dataset.kategori !== sel.value;
        });
    });
}

// Checkout: ganti antar/ambil, hitung ongkir & total
function initCheckout() {
    const form = document.getElementById('checkout-form');
    if (!form) return;
    const subtotal = Number(form.dataset.subtotal);
    const ongkir = Number(form.dataset.ongkir);
    const money = (v) => 'Rp ' + Math.round(v).toLocaleString('id-ID');
    const fields = document.getElementById('checkout-fields');

    const paymentLabels = {
        CASH: 'Tunai',
        QRIS: 'QRIS',
        BANK_TRANSFER: 'Transfer Bank',
    };
    const paymentNotes = {
        CASH: 'Pembayaran tunai dilakukan langsung di gerai / saat pesanan diantar.',
        QRIS: 'Kamu akan diarahkan ke Midtrans untuk scan QRIS setelah pesanan dibuat.',
        BANK_TRANSFER: 'Kamu akan diarahkan ke Midtrans untuk pembayaran Virtual Account setelah pesanan dibuat.',
    };

    const sync = (animate) => {
        const antar = (form.querySelector('[data-type]:checked')?.value || 'antar') === 'antar';
        const method = form.querySelector('[data-payment-method]:checked')?.value || 'CASH';
        const isCash = method === 'CASH';

        form.querySelectorAll('[data-antar]').forEach((el) => (el.hidden = !antar));
        form.querySelectorAll('[data-ambil]').forEach((el) => (el.hidden = antar));
        form.querySelectorAll('[data-label-antar]').forEach((el) => (el.hidden = !antar));
        form.querySelectorAll('[data-label-ambil]').forEach((el) => (el.hidden = antar));
        document.getElementById('address').required = antar;

        const shipping = antar ? ongkir : 0;
        form.querySelector('[data-shipping-label]').textContent = shipping ? money(shipping) : 'Gratis';
        form.querySelector('[data-type-label]').textContent = antar ? 'Diantar' : 'Ambil sendiri';
        form.querySelector('[data-total]').textContent = money(subtotal + shipping);

        form.querySelector('[data-payment-method-label]').textContent = paymentLabels[method] || 'Tunai';
        form.querySelector('[data-submit-label]').textContent = isCash ? 'Buat Pesanan' : 'Buat Pesanan & Bayar';
        form.querySelector('[data-payment-note]').textContent = paymentNotes[method] || paymentNotes.CASH;

        if (animate && fields) {
            fields.classList.remove('animate-fade-up');
            void fields.offsetWidth;
            fields.classList.add('animate-fade-up');
        }
    };

    // Cegah klik submit kedua. Idempotensi tetap dijaga server lewat checkout_token.
    form.addEventListener('submit', (e) => {
        if (form.dataset.submitting === '1') {
            e.preventDefault();
            return;
        }
        form.dataset.submitting = '1';
        const button = form.querySelector('button[type="submit"]');
        if (button) {
            button.disabled = true;
            const label = button.querySelector('[data-submit-label]');
            if (label) label.textContent = 'Memproses...';
        }
    });

    form.querySelectorAll('[data-type]').forEach((r) => r.addEventListener('change', () => sync(true)));
    form.querySelectorAll('[data-payment-method]').forEach((r) => r.addEventListener('change', () => sync(false)));
    sync(false);
}

document.addEventListener('DOMContentLoaded', () => {
    initCheckout();
    initKatalog();
    initFilterKategori();
});
