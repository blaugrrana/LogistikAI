<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar — LogistikAI</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=JetBrains+Mono:wght@400;500;600&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">

<div class="aurora" aria-hidden="true"></div>
<div class="grain" aria-hidden="true"></div>

<div class="relative flex min-h-screen items-center justify-center overflow-hidden p-8">
    <div class="glass-lg relative flex w-full max-w-[420px] flex-col gap-5 p-8">
        <div class="flex flex-col gap-1.5">
            <a href="/" class="eyebrow no-underline">Daftar</a>
            <div style="font-family: var(--font-display); font-weight: 700; font-size: 1.953rem; letter-spacing: -0.015em; color: var(--c-frost)">LogistikAI</div>
            <p class="t-body max-w-[44ch]" style="font-size: var(--fs-body-s)">Buat akun untuk mulai menata penempatan barang di gudang Anda.</p>
        </div>

        @if ($errors->any())
            <div class="rounded-xl p-3 text-[13px]" style="background: color-mix(in oklab, var(--c-risk) 12%, transparent); color: color-mix(in oklab, var(--c-risk) 65%, black)">
                {{ $errors->first() }}
            </div>
        @endif

        <form class="flex flex-col gap-3.5" method="POST" action="{{ route('register') }}">
            @csrf
            <div class="field">
                <label for="company" class="field__label">Nama perusahaan</label>
                <input id="company" name="company" type="text" required maxlength="255"
                       value="{{ old('company') }}" placeholder="PT Maju Jaya" class="field__input">
                <span class="footnote" style="letter-spacing: normal; text-transform: none">
                    Nama yang sama akan bergabung ke ruang kerja perusahaan yang sudah ada.
                </span>
            </div>
            <div class="field">
                <label for="name" class="field__label">Nama lengkap</label>
                <input id="name" name="name" type="text" required autocomplete="name" value="{{ old('name') }}" placeholder="Nama Anda" class="field__input">
            </div>
            <div class="field">
                <label for="email" class="field__label">Alamat email</label>
                <input id="email" name="email" type="email" required autocomplete="email" value="{{ old('email') }}" placeholder="anda@perusahaan.id" class="field__input">
            </div>
            <div class="field">
                <label for="password" class="field__label">Kata sandi</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" minlength="8" placeholder="Minimal 8 karakter" class="field__input">
            </div>
            <div class="field">
                <label for="password_confirmation" class="field__label">Konfirmasi kata sandi</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" minlength="8" placeholder="Ulangi kata sandi" class="field__input">
            </div>

            <button type="submit" class="btn btn--primary btn--full">Buat akun</button>

            <p class="footnote text-center" style="letter-spacing: normal; text-transform: none">
                Sudah punya akun? <a href="/login" style="color: var(--c-cobalt)">Masuk di sini</a>
            </p>
        </form>
    </div>
</div>

</body>
</html>
