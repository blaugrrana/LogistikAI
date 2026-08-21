<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $tab === 'akun' ? 'Pengaturan Akun' : 'Manajemen Barang' }} — LogistikAI</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=JetBrains+Mono:wght@400;500;600&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">

<div class="aurora" aria-hidden="true"></div>
<div class="grain" aria-hidden="true"></div>

<div class="relative min-h-screen pb-12">

    {{-- ── Navigasi atas ────────────────────────────────────────────── --}}
    <header class="glass-md sticky top-3 z-20 mx-3 mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 rounded-xl px-2.5 py-1.5 sm:mx-6 sm:flex-nowrap">
        <a href="/" class="no-underline" style="font-family: var(--font-display); font-weight: 700; font-size: 0.9375rem; letter-spacing: -0.02em; color: var(--c-frost); padding-right: 4px">LogistikAI</a>
        <nav class="order-last flex w-full min-w-0 flex-1 gap-0.5 overflow-x-auto sm:order-none sm:w-auto">
            <a href="{{ route('dashboard') }}" class="navtab whitespace-nowrap" @if ($tab === 'barang') aria-current="page" @endif>Manajemen Barang</a>
            <a href="{{ route('dashboard', ['tab' => 'akun']) }}" class="navtab whitespace-nowrap" @if ($tab === 'akun') aria-current="page" @endif>Pengaturan Akun</a>
        </nav>
        <div class="ml-auto flex shrink-0 items-center gap-2.5 sm:ml-0">
            <span class="footnote hidden sm:block">{{ now()->format('H:i') }} WIB</span>
            <span class="flex h-6 w-6 items-center justify-center rounded-full" style="background: var(--c-basalt); border: 1px solid var(--glass-edge); font-family: var(--font-mono); font-size: 10px; color: var(--c-frost)">
                {{ Str::upper(Str::substr(auth()->user()->name, 0, 2)) }}
            </span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn--secondary btn--sm">Keluar</button>
            </form>
        </div>
    </header>

    @if ($tab === 'barang')
        <div class="relative mx-3 mt-2 sm:mx-6">
            @include('dashboard.chat')
        </div>
    @endif

    {{-- ── Judul + status ───────────────────────────────────────────── --}}
    <div class="relative flex flex-wrap items-end justify-between gap-4 px-4 pt-5 sm:px-6">
        <div>
            <div class="eyebrow">{{ auth()->user()->company->name }}</div>
            <div class="t-h2">{{ $tab === 'akun' ? 'Pengaturan Akun' : 'Manajemen Barang' }}</div>
        </div>
        @if ($tab === 'barang' && $warehouses->isNotEmpty())
            <form method="POST" action="{{ route('items.spot') }}">
                @csrf
                <button type="submit" class="btn btn--primary" @disabled(! $aiReady)>Jalankan spotting AI</button>
            </form>
        @endif
    </div>

    <div class="relative flex flex-col gap-4 px-4 pt-5 sm:px-6">

        @if (session('status'))
            <div class="notice notice--ok">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="notice notice--risk">{{ $errors->first() }}</div>
        @endif

        @unless ($aiReady)
            <div class="notice notice--warn">
                <strong>GEMINI_API_KEY belum diisi.</strong> Tambahkan kuncinya di file <code>.env</code> lalu jalankan
                <code>php artisan config:clear</code> agar penyusunan gudang dan spotting otomatis bisa dipakai.
            </div>
        @endunless

        @if ($tab === 'barang' && ($lowStockItems->isNotEmpty() || $expiringItems->isNotEmpty()))
            <div class="notice notice--warn">
                @if ($lowStockItems->isNotEmpty()) {{ $lowStockItems->count() }} SKU di bawah stok minimum. @endif
                @if ($expiringItems->isNotEmpty()) {{ $expiringItems->count() }} SKU mendekati kedaluwarsa. @endif
            </div>
        @endif

        @if ($tab === 'akun')
            @include('dashboard.akun')
        @else
            @include('dashboard.barang')
        @endif
    </div>
</div>

@stack('scripts')
</body>
</html>
