<x-layouts.auth title="Daftar" subtitle="Buat akun untuk menyimpan riwayat pesananmu"
    heading="Mulai Perjalanan Matcha-mu" description="Daftar gratis dan nikmati kemudahan memesan matcha favoritmu.">
    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="label-matcha" for="name">Nama lengkap</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                class="input-matcha" placeholder="Nama lengkap">
        </div>

        <div>
            <label class="label-matcha" for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required
                class="input-matcha" placeholder="Masukkan email Anda">
        </div>

        <div>
            <label class="label-matcha" for="password">Password</label>
            <div class="relative">
                <input id="password" name="password" type="password" required
                    class="input-matcha pr-11" placeholder="Minimal 6 karakter">
                <button type="button" id="toggle-password" aria-label="Tampilkan sandi"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 transition hover:text-matcha-700">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>

        @if ($errors->any())
            <div class="animate-pop-in rounded-xl bg-red-50 px-3 py-2 text-sm text-red-700" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <button type="submit" class="btn-matcha w-full">Daftar</button>
    </form>

    <p class="mt-6 text-center text-sm text-stone-500">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="font-semibold text-matcha-700 hover:underline">Masuk di sini</a>
    </p>

    <script>
        document.getElementById('toggle-password')?.addEventListener('click', function () {
            const input = document.getElementById('password');
            const icon = this.querySelector('i');
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            icon.classList.toggle('bi-eye', !show);
            icon.classList.toggle('bi-eye-slash', show);
        });
    </script>
</x-layouts.auth>
