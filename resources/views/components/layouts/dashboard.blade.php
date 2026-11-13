@props(['nav' => [], 'roleLabel' => 'Konsumen', 'title' => '', 'subtitle' => '', 'showCart' => false])

@php
    $cartCount = $showCart ? app(\App\Services\CartService::class)->count() : 0;
    $initial = strtoupper(mb_substr(auth()->user()?->name ?? 'U', 0, 1));
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?: 'Dashboard' }} · Matcha and Me</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-matcha-50">
    <div class="min-h-screen lg:flex">
        {{-- Sidebar desktop --}}
        <aside class="sticky top-0 hidden h-screen w-64 shrink-0 border-r border-matcha-100 bg-matcha-100/40 lg:block">
            <div class="flex h-full flex-col">
                <div class="px-5 pb-4 pt-5">
                    <x-logo />
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto px-3 pb-4">
                    @foreach ($nav as $item)
                        @php($active = request()->routeIs($item['active'] ?? ''))
                        <a href="{{ $item['url'] }}"
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium no-underline transition {{ $active ? 'bg-matcha-700 text-white shadow-sm' : 'text-stone-600 hover:bg-matcha-100' }}">
                            <i class="bi {{ $item['icon'] }}"></i>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                @auth
                    <div class="m-3 flex items-center gap-3 rounded-xl bg-matcha-100/70 p-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-matcha-700 text-sm font-bold text-white">{{ $initial }}</div>
                        <div class="min-w-0 flex-1 leading-tight">
                            <div class="truncate text-sm font-semibold text-matcha-900">{{ auth()->user()->name }}</div>
                            <div class="truncate text-xs text-stone-500">{{ $roleLabel }}</div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" title="Keluar" class="rounded-lg p-1.5 text-stone-500 transition hover:bg-white hover:text-matcha-700">
                                <i class="bi bi-box-arrow-right"></i>
                            </button>
                        </form>
                    </div>
                @else
                    <div class="m-3 rounded-xl bg-matcha-100/70 p-3">
                        <div class="text-sm font-semibold text-matcha-900">Belum masuk</div>
                        <p class="mt-0.5 text-xs text-stone-500">Masuk agar riwayat pesanan tersimpan di akunmu.</p>
                        <div class="mt-2 flex gap-2">
                            <a href="{{ route('login') }}" class="btn-matcha flex-1 !py-1.5 text-xs no-underline">Masuk</a>
                            <a href="{{ route('register') }}" class="btn-outline-matcha flex-1 !py-1.5 text-xs no-underline">Daftar</a>
                        </div>
                    </div>
                @endauth
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-30 border-b border-matcha-100 bg-matcha-50/90 px-4 py-3 backdrop-blur sm:px-8">
                <div class="flex items-center gap-3">
                    <div class="min-w-0 flex-1">
                        <h1 class="truncate text-lg font-bold leading-tight text-matcha-900">{{ $title }}</h1>
                        <p class="truncate text-xs text-stone-500">{{ $subtitle }}</p>
                    </div>

                    @if ($showCart)
                        <a href="{{ route('cart.index') }}" class="relative flex h-9 w-9 items-center justify-center rounded-xl border border-matcha-200 bg-white text-matcha-700 no-underline transition hover:border-matcha-500" aria-label="Keranjang">
                            <i class="bi bi-bag"></i>
                            @if ($cartCount > 0)
                                <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-matcha-700 px-1 text-[10px] font-bold text-white">{{ $cartCount }}</span>
                            @endif
                        </a>
                    @endif

                    @auth
                        <div class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-matcha-200 text-sm font-bold text-matcha-800">{{ $initial }}</div>
                            <span class="hidden text-sm font-semibold text-matcha-900 sm:inline">
                                {{ auth()->user()->isAdmin() ? 'Admin' : explode(' ', auth()->user()->name)[0] }}
                            </span>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="btn-matcha !py-1.5 text-xs no-underline">
                            <i class="bi bi-box-arrow-in-right"></i> Masuk
                        </a>
                    @endauth
                </div>

                {{-- Navigasi mobile --}}
                <nav class="-mx-1 mt-2 flex gap-1 overflow-x-auto pb-0.5 lg:hidden">
                    @foreach ($nav as $item)
                        @php($active = request()->routeIs($item['active'] ?? ''))
                        <a href="{{ $item['url'] }}"
                            class="flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold no-underline transition {{ $active ? 'bg-matcha-700 text-white' : 'text-stone-600 hover:bg-matcha-100' }}">
                            <i class="bi {{ $item['icon'] }}"></i>{{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            </header>

            <main class="px-4 py-6 sm:px-8">
                @if (session('success'))
                    <div class="mb-4 animate-pop-in rounded-xl bg-matcha-100 px-4 py-2.5 text-sm font-semibold text-matcha-800" role="status">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="mb-4 animate-pop-in rounded-xl bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700" role="alert">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 animate-pop-in rounded-xl bg-red-50 px-4 py-2.5 text-sm text-red-700" role="alert">{{ $errors->first() }}</div>
                @endif

                <div class="page-enter">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</body>
</html>
