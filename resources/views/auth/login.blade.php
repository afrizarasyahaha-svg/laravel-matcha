<x-layouts.auth title="Login" subtitle="Masuk ke akun Anda untuk melanjutkan">
    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="label-matcha" for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                class="input-matcha" placeholder="Masukkan email Anda">
        </div>

        <div>
            <label class="label-matcha" for="password">Password</label>
            <input id="password" name="password" type="password" required
                class="input-matcha" placeholder="Masukkan password Anda">
        </div>

        <div class="flex items-center justify-between text-sm">
            <label class="flex cursor-pointer items-center gap-2 text-stone-600">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))
                    class="h-4 w-4 rounded border-matcha-300 text-matcha-700 focus:ring-matcha-400">
                Ingat saya
            </label>
        </div>

        @if ($errors->any())
            <div class="animate-pop-in rounded-xl bg-red-50 px-3 py-2 text-sm text-red-700" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <button type="submit" class="btn-matcha w-full">Masuk</button>
    </form>

    <p class="mt-6 text-center text-sm text-stone-500">
        Belum punya akun?
        <a href="{{ route('register') }}" class="font-semibold text-matcha-700 hover:underline">Daftar di sini</a>
    </p>
</x-layouts.auth>
