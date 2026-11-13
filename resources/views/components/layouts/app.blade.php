@props(['title' => 'Matcha and Me'])

@php
    $cartCount = app(\App\Services\CartService::class)->count();
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · Matcha and Me</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-matcha-50">
    <header class="relative z-20 mx-auto flex max-w-[1600px] animate-fade-in items-center gap-4 px-6 py-5 sm:px-10 lg:px-16">
        <x-logo />

        <nav class="ml-6 hidden items-center gap-6 md:flex">
            <a href="{{ route('landing') }}" class="text-sm font-medium no-underline transition {{ request()->routeIs('landing') ? 'border-b-2 border-matcha-700 pb-0.5 text-matcha-900' : 'text-stone-600 hover:text-matcha-700' }}">Beranda</a>
            <a href="{{ route('katalog.index') }}" class="text-sm font-medium no-underline transition {{ request()->routeIs('katalog.*') ? 'border-b-2 border-matcha-700 pb-0.5 text-matcha-900' : 'text-stone-600 hover:text-matcha-700' }}">Katalog</a>
            <a href="{{ route('orders.history') }}" class="text-sm font-medium no-underline transition {{ request()->routeIs('orders.history') ? 'border-b-2 border-matcha-700 pb-0.5 text-matcha-900' : 'text-stone-600 hover:text-matcha-700' }}">Riwayat Pesanan</a>
        </nav>

        <div class="ml-auto flex items-center gap-2 sm:gap-3">
            <a href="{{ route('cart.index') }}" class="relative flex h-9 w-9 items-center justify-center rounded-full border border-matcha-200 bg-white text-matcha-700 no-underline transition hover:border-matcha-500" aria-label="Keranjang">
                <i class="bi bi-bag"></i>
                @if ($cartCount > 0)
                    <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-matcha-700 px-1 text-[10px] font-bold text-white">{{ $cartCount }}</span>
                @endif
            </a>

            @auth
                <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('beranda') }}" class="btn-matcha !rounded-full !py-1.5 no-underline">
                    {{ auth()->user()->isAdmin() ? 'Dashboard' : 'Akun Saya' }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-full border border-matcha-700 px-4 py-1.5 text-sm font-semibold text-matcha-700 transition hover:bg-white" title="Keluar">
                        <i class="bi bi-box-arrow-right"></i>
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="rounded-full border border-matcha-700 px-4 py-1.5 text-sm font-semibold text-matcha-700 no-underline transition hover:bg-white">Masuk</a>
                <a href="{{ route('register') }}" class="btn-matcha !rounded-full !py-1.5 no-underline">Daftar</a>
            @endauth
        </div>
    </header>

    @if (session('success'))
        <div class="mx-auto max-w-[1600px] px-6 sm:px-10 lg:px-16">
            <div class="animate-pop-in rounded-xl bg-matcha-100 px-4 py-2.5 text-sm font-semibold text-matcha-800" role="status">
                {{ session('success') }}
            </div>
        </div>
    @endif
    @if (session('error'))
        <div class="mx-auto max-w-[1600px] px-6 sm:px-10 lg:px-16">
            <div class="animate-pop-in rounded-xl bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700" role="alert">
                {{ session('error') }}
            </div>
        </div>
    @endif
    @if ($errors->any())
        <div class="mx-auto max-w-[1600px] px-6 sm:px-10 lg:px-16">
            <div class="animate-pop-in rounded-xl bg-red-50 px-4 py-2.5 text-sm text-red-700" role="alert">
                {{ $errors->first() }}
            </div>
        </div>
    @endif

    <main class="page-enter">
        {{ $slot }}
    </main>

    <footer class="relative mt-16 overflow-hidden bg-matcha-900 text-matcha-100">
        <div class="relative mx-auto grid max-w-[1600px] gap-8 px-6 py-10 sm:grid-cols-2 sm:px-10 lg:px-16">
            <div>
                <x-logo :light="true" />
                <p class="mt-3 max-w-sm text-sm text-matcha-200">
                    Matcha and Me menyajikan matcha premium grade Uji yang disiapkan segar untuk setiap momen.
                </p>
            </div>
            <div class="text-sm">
                <div class="mb-2 font-semibold text-white">Kontak</div>
                <p class="text-matcha-200">Gerai Telang · Surabaya</p>
                <p class="text-matcha-200">halo@matchaandme.id</p>
            </div>
        </div>
    </footer>
</body>
</html>
