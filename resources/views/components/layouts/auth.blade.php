@props([
    'title' => 'Masuk',
    'subtitle' => '',
    'heading' => 'Selamat Datang di Matcha and Me!',
    'description' => 'Nikmati kesegaran matcha terbaik dalam setiap tegukan.',
])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · Matcha and Me</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-matcha-50">
    <div class="mx-auto grid min-h-screen max-w-[1500px] items-center gap-10 px-5 py-8 sm:px-8 lg:grid-cols-2 lg:px-16">
        {{-- Panel branding --}}
        <div class="relative hidden h-full min-h-[560px] flex-col justify-between overflow-hidden rounded-[2rem] bg-gradient-to-br from-matcha-200 via-matcha-100 to-matcha-300 p-10 lg:flex">
            <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 animate-blob rounded-full bg-matcha-300/60 blur-2xl"></div>
            <div class="pointer-events-none absolute -bottom-20 -left-10 h-64 w-64 animate-blob rounded-full bg-matcha-400/30 blur-2xl [animation-delay:2s]"></div>

            <a href="{{ route('landing') }}" class="relative z-10 inline-flex items-center gap-1.5 text-sm font-semibold text-matcha-700 no-underline transition hover:text-matcha-900">
                <i class="bi bi-arrow-left"></i> Kembali ke beranda
            </a>

            <div class="relative z-10 max-w-md animate-fade-up">
                <x-logo class="mb-8" />
                <h1 class="text-3xl font-extrabold leading-tight text-matcha-900">{{ $heading }}</h1>
                <p class="mt-3 text-sm text-stone-600">{{ $description }}</p>
            </div>

            <div class="relative z-10 mx-auto h-56 w-40 animate-float">
                <x-drink-image tone="latte" :rounded="false" alt="Iced matcha latte" class="!bg-transparent" />
            </div>
        </div>

        {{-- Kartu form --}}
        <div class="mx-auto w-full max-w-md animate-pop-in rounded-3xl bg-white p-7 shadow-soft sm:p-8">
            <a href="{{ route('landing') }}" class="group mb-4 inline-flex items-center gap-1.5 rounded-full py-1 pr-3 text-sm font-semibold text-matcha-700 no-underline transition hover:text-matcha-900 lg:hidden">
                <i class="bi bi-arrow-left transition-transform duration-200 group-hover:-translate-x-1"></i> Kembali
            </a>

            <div class="mb-6 text-center lg:text-left">
                <x-logo class="mb-5 lg:hidden" />
                <h2 class="text-2xl font-extrabold text-matcha-900">{{ $title }}</h2>
                @if ($subtitle)
                    <p class="mt-1 text-sm text-stone-500">{{ $subtitle }}</p>
                @endif
            </div>

            {{ $slot }}
        </div>
    </div>
</body>
</html>
