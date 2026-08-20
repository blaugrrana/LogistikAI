<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LogistikAI — Penempatan Barang Gudang Berbasis AI</title>
    <meta name="description" content="LogistikAI mengatur penempatan barang per rak dan gudang berdasarkan kategori, frekuensi pergerakan, dan kapasitas rak — lengkap dengan denah 2D interaktif, heatmap pergerakan, dan laporan stok opname.">
    <link rel="icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=JetBrains+Mono:wght@400;500;600&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">

<div class="aurora" aria-hidden="true"></div>
<div class="grain" aria-hidden="true"></div>

@php
    // Data contoh untuk denah 2D & heatmap (statis, hanya untuk demo landing page).
    $racks = [
        ['code' => 'A1', 'zone' => 'Zona A — Fast Moving', 'category' => 'Minuman Kaleng', 'sku' => 42, 'cap' => 96, 'mov' => 92, 'exp' => 0],
        ['code' => 'A2', 'zone' => 'Zona A — Fast Moving', 'category' => 'Snack Kemasan',  'sku' => 38, 'cap' => 88, 'mov' => 85, 'exp' => 2],
        ['code' => 'A3', 'zone' => 'Zona A — Fast Moving', 'category' => 'Air Mineral',    'sku' => 12, 'cap' => 74, 'mov' => 78, 'exp' => 0],
        ['code' => 'A4', 'zone' => 'Zona A — Fast Moving', 'category' => 'Mi Instan',      'sku' => 27, 'cap' => 61, 'mov' => 70, 'exp' => 1],
        ['code' => 'B1', 'zone' => 'Zona B — Medium',      'category' => 'Sembako',        'sku' => 55, 'cap' => 83, 'mov' => 54, 'exp' => 3],
        ['code' => 'B2', 'zone' => 'Zona B — Medium',      'category' => 'Bumbu Dapur',    'sku' => 61, 'cap' => 47, 'mov' => 46, 'exp' => 5],
        ['code' => 'B3', 'zone' => 'Zona B — Medium',      'category' => 'Produk Susu',    'sku' => 19, 'cap' => 92, 'mov' => 51, 'exp' => 8],
        ['code' => 'B4', 'zone' => 'Zona B — Medium',      'category' => 'Kebersihan',     'sku' => 33, 'cap' => 38, 'mov' => 33, 'exp' => 0],
        ['code' => 'C1', 'zone' => 'Zona C — Slow Moving', 'category' => 'Alat Tulis',     'sku' => 24, 'cap' => 29, 'mov' => 18, 'exp' => 0],
        ['code' => 'C2', 'zone' => 'Zona C — Slow Moving', 'category' => 'Perkakas',       'sku' => 16, 'cap' => 55, 'mov' => 12, 'exp' => 0],
        ['code' => 'C3', 'zone' => 'Zona C — Slow Moving', 'category' => 'Musiman',        'sku' => 9,  'cap' => 22, 'mov' => 8,  'exp' => 1],
        ['code' => 'C4', 'zone' => 'Zona C — Slow Moving', 'category' => 'Retur / Karantina', 'sku' => 7, 'cap' => 14, 'mov' => 4, 'exp' => 6],
    ];
@endphp

{{-- ── Header pil melayang ──────────────────────────────────────────── --}}
<header class="glass-md sticky top-3 z-20 mx-3 mt-3 flex max-w-[1080px] items-center gap-5 rounded-xl px-3.5 py-2 lg:mx-auto">
    <a href="#top" class="font-bold tracking-tight text-frost no-underline" style="font-family: var(--font-display); font-size: 1rem; letter-spacing: -0.02em">LogistikAI</a>
    <nav class="flex flex-1 gap-0.5 overflow-x-auto">
        <a href="#produk" class="rounded-lg px-2.5 py-1.5 text-[13px] text-mist no-underline hover:text-frost">Produk</a>
        <a href="#denah" class="rounded-lg px-2.5 py-1.5 text-[13px] text-mist no-underline hover:text-frost">Denah</a>
        <a href="#laporan" class="rounded-lg px-2.5 py-1.5 text-[13px] text-mist no-underline hover:text-frost">Laporan</a>
        <a href="#cara" class="hidden rounded-lg px-2.5 py-1.5 text-[13px] text-mist no-underline hover:text-frost sm:block">Cara kerja</a>
    </nav>
    <a href="/login" class="btn btn--primary btn--sm">Login</a>
</header>

{{-- ── Hero ─────────────────────────────────────────────────────────── --}}
<section id="top" class="relative mx-auto flex max-w-[1080px] flex-col items-center gap-5 px-6 pt-[88px] pb-14 text-center">
    <span class="eyebrow">Panel penempatan berbasis model</span>
    <h1 class="t-h1 max-w-[16ch]">Setiap barang punya rak yang tepat</h1>
    <p class="t-lead max-w-[60ch]">
        LogistikAI membaca kategori, frekuensi pergerakan, dan kapasitas rak dari seluruh gudang Anda,
        lalu menunjuk satu hal yang paling perlu dipindahkan hari ini.
    </p>
    <div class="flex flex-wrap justify-center gap-2.5 pt-1">
        <a href="/login" class="btn btn--primary">Login</a>
        <a href="/register" class="btn btn--secondary">Register</a>
    </div>
    <div class="footnote">Tanpa kartu kredit · Pasang dalam satu hari</div>
</section>

{{-- ── Pratinjau panel + strip metrik ───────────────────────────────── --}}
<section class="relative mx-auto max-w-[1080px] px-6">
    <div class="panel flex flex-col gap-3.5 p-5">
        <div class="flex items-center justify-between gap-3">
            <span class="eyebrow">Pratinjau panel</span>
            <span class="badge badge--ok">Data langsung</span>
        </div>
        <div class="grid gap-3.5 sm:grid-cols-3">
            <div class="metric">
                <span class="metric__label">SKU terpantau</span>
                <span class="metric__value">1.284</span>
                <span class="metric__delta metric__delta--up">↑ 42 sejak kemarin</span>
            </div>
            <div class="metric">
                <span class="metric__label">Akurasi stok opname</span>
                <span class="metric__value">99,4%</span>
                <span class="metric__delta metric__delta--up">↑ 1,8% bulan ini</span>
            </div>
            <div class="metric">
                <span class="metric__label">Jarak tempuh picking</span>
                <span class="metric__value">-38%</span>
                <span class="metric__delta metric__delta--up">↓ berkat penataan ulang</span>
            </div>
        </div>
    </div>
</section>

{{-- ── Produk: tiga variabel penempatan ─────────────────────────────── --}}
<section id="produk" class="relative mx-auto grid max-w-[1080px] gap-4 px-6 pt-[72px] md:grid-cols-3">
    <div class="glass-md glass-hover flex flex-col gap-2 p-6">
        <span class="eyebrow">01 · Kelompokkan</span>
        <h3 class="t-h3">Kategori menentukan tetangga rak</h3>
        <p class="t-body">Barang sejenis dikumpulkan agar mudah dicari, aman dari kontaminasi silang, dan sesuai syarat penyimpanan.</p>
    </div>
    <div class="glass-md glass-hover flex flex-col gap-2 p-6">
        <span class="eyebrow">02 · Ukur</span>
        <h3 class="t-h3">Frekuensi menentukan jaraknya</h3>
        <p class="t-body">Analisis fast, medium, dan slow moving dari riwayat transaksi menempatkan barang laris paling dekat dok pengiriman.</p>
    </div>
    <div class="glass-md glass-hover flex flex-col gap-2 p-6">
        <span class="eyebrow">03 · Jaga</span>
        <h3 class="t-h3">Kapasitas menentukan batasnya</h3>
        <p class="t-body">Volume, beban, dan sisa slot tiap rak dijaga agar tidak ada rak jebol maupun ruang yang terbuang percuma.</p>
    </div>
</section>

{{-- ── Denah 2D interaktif + heatmap ────────────────────────────────── --}}
<section id="denah" class="relative mx-auto max-w-[1080px] px-6 pt-[72px]">
    <div class="flex flex-col gap-3.5 pb-6">
        <span class="eyebrow">Visual gudang</span>
        <h2 class="t-h2 max-w-[22ch]">Denah 2D interaktif dan heatmap pergerakan</h2>
        <p class="t-body max-w-[62ch]" style="font-size: var(--fs-body)">Klik rak mana pun untuk membaca isinya. Ganti mode untuk membandingkan kapasitas terpakai dengan intensitas pergerakan barang.</p>
    </div>

    <div class="grid gap-4 lg:grid-cols-[1.5fr_1fr]">
        <div class="glass-md flex flex-col gap-4 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="glass-sm inline-flex gap-1 rounded-full p-1">
                    <button data-mode="cap" class="mode-btn rounded-full px-3.5 py-1.5 text-[13px] font-semibold">Kapasitas</button>
                    <button data-mode="mov" class="mode-btn rounded-full px-3.5 py-1.5 text-[13px] font-semibold">Heatmap</button>
                </div>
                <div class="flex items-center gap-2 text-[11px] text-slate">
                    <span>Rendah</span>
                    <span id="legend-bar" class="h-2 w-24 rounded-full"></span>
                    <span>Tinggi</span>
                </div>
            </div>

            <div class="grid-floor rounded-2xl border p-4" style="border-color: var(--glass-edge); background: rgba(255,255,255,.4)">
                <div class="mb-3 flex items-center justify-between">
                    <span class="eyebrow">Gudang Pusat — Lantai 1</span>
                    <span class="footnote">1 kotak ≈ 1 bay</span>
                </div>
                <div id="rack-grid" class="grid grid-cols-4 gap-2.5">
                    @foreach ($racks as $r)
                        <button type="button" aria-pressed="false"
                            class="rack relative aspect-4/3 p-2.5 text-left focus:outline-none"
                            data-code="{{ $r['code'] }}" data-zone="{{ $r['zone'] }}" data-category="{{ $r['category'] }}"
                            data-sku="{{ $r['sku'] }}" data-cap="{{ $r['cap'] }}" data-mov="{{ $r['mov'] }}" data-exp="{{ $r['exp'] }}">
                            <span class="text-[13px] font-semibold text-frost">{{ $r['code'] }}</span>
                            <span class="block text-[11px] leading-tight text-mist">{{ $r['category'] }}</span>
                            <span class="rack-metric absolute bottom-2 right-2 font-mono text-[11px] tabular-nums text-mist" style="font-family: var(--font-mono)">{{ $r['cap'] }}%</span>
                            @if ($r['exp'] > 0)
                                <span class="absolute right-2 top-2 h-2 w-2 rounded-full" style="background: var(--c-warn)" title="{{ $r['exp'] }} batch mendekati kedaluwarsa"></span>
                            @endif
                        </button>
                    @endforeach
                </div>
                <div class="mt-3 rounded-lg px-3 py-2" style="background: linear-gradient(90deg, color-mix(in oklab, var(--c-cobalt) 18%, transparent), transparent)">
                    <span class="footnote">Dok muat &amp; area pengiriman</span>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-4">
            <div class="glass-md flex flex-col gap-4 p-6">
                <span class="eyebrow">Detail rak</span>
                <h3 class="t-h3"><span data-f="code">A1</span><span class="block text-[13px] font-normal text-slate" data-f="zone">Zona A — Fast Moving</span></h3>
                <dl class="grid grid-cols-2 gap-4 text-[13px]">
                    <div><dt class="text-slate">Kategori</dt><dd class="mt-0.5 font-semibold text-frost" data-f="category">Minuman Kaleng</dd></div>
                    <div><dt class="text-slate">Jumlah SKU</dt><dd class="mt-0.5 font-semibold text-frost" data-f="sku">42</dd></div>
                    <div><dt class="text-slate">Kapasitas terpakai</dt><dd class="mt-0.5 font-semibold text-frost" data-f="capv">96%</dd></div>
                    <div><dt class="text-slate">Indeks pergerakan</dt><dd class="mt-0.5 font-semibold text-frost" data-f="movv">92 / 100</dd></div>
                </dl>
                <p class="rounded-xl p-3 text-[13px] leading-relaxed text-mist" style="background: rgba(255,255,255,.5)" data-f="note">Rak padat dan sangat aktif — kandidat pemecahan stok ke rak sebelah.</p>
            </div>

            <div class="glass-md overflow-hidden">
                <div class="relative aspect-video" style="background: linear-gradient(135deg, color-mix(in oklab, var(--c-jade) 22%, transparent), color-mix(in oklab, var(--c-orchid) 22%, transparent))">
                    <div class="grid-floor absolute inset-0 opacity-60"></div>
                    <div class="absolute inset-x-6 bottom-6 top-8 flex items-end gap-2">
                        @foreach ([65, 80, 48, 92, 70, 58] as $h)
                            <div class="flex-1 rounded-t-sm" style="height: {{ $h }}%; background: linear-gradient(to top, rgba(255,255,255,.75), rgba(255,255,255,.3)); box-shadow: inset 0 0 0 1px var(--glass-edge)"></div>
                        @endforeach
                    </div>
                    <span class="badge badge--info absolute left-4 top-4">Foto · <span data-f="code">A1</span></span>
                </div>
                <div class="p-5">
                    <p class="text-[14px] font-semibold text-frost">Foto kondisi gudang per lokasi</p>
                    <p class="t-body mt-1" style="font-size: var(--fs-body-s)">Unggah dari HP saat opname; tersimpan lengkap dengan waktu, petugas, dan kode rak.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── Informasi operasional ────────────────────────────────────────── --}}
<section class="relative mx-auto max-w-[1080px] px-6 pt-[72px]">
    <div class="flex flex-col gap-3.5 pb-6">
        <span class="eyebrow">Informasi</span>
        <h2 class="t-h2 max-w-[22ch]">Angka yang selalu siap dibaca</h2>
    </div>
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="glass-md flex flex-col gap-4 p-6">
            <h3 class="t-h3" style="font-size: var(--fs-body)">Level stok real-time per SKU &amp; lokasi</h3>
            <p class="t-body" style="font-size: var(--fs-body-s)">Setiap penerimaan, pemindahan, dan pengeluaran langsung mengubah angka stok di lokasi terkait.</p>
            <div class="flex flex-col gap-2.5">
                @foreach ([['SKU-10482', 'A3 · Bay 04', 1280, 78], ['SKU-77120', 'B3 · Bay 01', 340, 41], ['SKU-30915', 'C2 · Bay 07', 96, 14]] as [$sku, $lok, $qty, $pct])
                    <div>
                        <div class="flex items-center justify-between text-[13px]">
                            <span class="text-mist">{{ $sku }} <span class="text-slate">· {{ $lok }}</span></span>
                            <span class="tabular-nums font-semibold text-frost">{{ number_format($qty, 0, ',', '.') }} pcs</span>
                        </div>
                        <div class="mt-1.5 h-1.5 rounded-full" style="background: color-mix(in oklab, var(--c-frost) 10%, transparent)">
                            <div class="h-full rounded-full" style="width: {{ $pct }}%; background: linear-gradient(90deg, var(--c-jade), var(--c-cobalt))"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="glass-md flex flex-col gap-4 p-6">
            <h3 class="t-h3" style="font-size: var(--fs-body)">Barang mendekati kedaluwarsa</h3>
            <p class="t-body" style="font-size: var(--fs-body-s)">Peringatan berjenjang per batch dengan saran prioritas keluar mengikuti kaidah FEFO.</p>
            <ul class="flex flex-col gap-2.5">
                @foreach ([['SKU-77120 · Batch 2411', '12 hari', 'risk'], ['SKU-55201 · Batch 2409', '23 hari', 'warn'], ['SKU-11033 · Batch 2408', '48 hari', 'ok']] as [$batch, $sisa, $tone])
                    <li class="flex items-center justify-between rounded-xl px-3 py-2.5 text-[13px]" style="background: rgba(255,255,255,.5)">
                        <span class="text-mist">{{ $batch }}</span>
                        <span class="badge badge--{{ $tone }}">{{ $sisa }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="glass-md flex flex-col gap-4 p-6">
            <h3 class="t-h3" style="font-size: var(--fs-body)">Riwayat pergerakan (kartu stok)</h3>
            <p class="t-body" style="font-size: var(--fs-body-s)">Jejak masuk, keluar, dan pemindahan antar rak lengkap dengan saldo berjalan serta nama pelaksana.</p>
            <ol class="flex flex-col gap-3 border-l pl-4 text-[13px]" style="border-color: color-mix(in oklab, var(--c-frost) 12%, transparent)">
                @foreach ([['Masuk 400 pcs', '18 Agu · A3 · Bay 04', 'var(--c-jade)'], ['Pindah rak C2 → A3', '17 Agu · 240 pcs', 'var(--c-cobalt)'], ['Keluar 180 pcs', '15 Agu · DO-88213', 'var(--c-risk)']] as [$aksi, $ket, $warna])
                    <li class="relative">
                        <span class="absolute -left-[21px] top-1.5 h-2 w-2 rounded-full" style="background: {{ $warna }}"></span>
                        <p class="font-semibold text-frost">{{ $aksi }}</p>
                        <p class="text-slate" style="font-family: var(--font-mono); font-size: 11px">{{ $ket }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</section>

{{-- ── Laporan ──────────────────────────────────────────────────────── --}}
<section id="laporan" class="relative mx-auto max-w-[1080px] px-6 pt-[72px]">
    <div class="flex flex-col gap-3.5 pb-6">
        <span class="eyebrow">Laporan</span>
        <h2 class="t-h2 max-w-[24ch]">Siap dibagikan ke manajemen</h2>
    </div>
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="glass-md glass-hover flex flex-col gap-3 p-6">
            <h3 class="t-h3" style="font-size: var(--fs-body)">Laporan stok opname</h3>
            <p class="t-body" style="font-size: var(--fs-body-s)">Selisih fisik vs sistem per rak dan per SKU, lengkap dengan foto bukti dan pengesahan petugas.</p>
            <div class="mt-1 flex flex-wrap gap-2">
                @foreach (['PDF', 'Excel', 'CSV'] as $fmt)
                    <span class="tag">{{ $fmt }}</span>
                @endforeach
            </div>
        </div>
        <div class="glass-md glass-hover flex flex-col gap-3 p-6">
            <h3 class="t-h3" style="font-size: var(--fs-body)">Laporan pergerakan periodik</h3>
            <p class="t-body" style="font-size: var(--fs-body-s)">Rekap harian, mingguan, atau bulanan: barang masuk, keluar, pemindahan antar rak, dan tren per kategori.</p>
            <div class="mt-1 flex h-20 items-end gap-1.5">
                @foreach ([38, 52, 44, 68, 60, 82, 74] as $h)
                    <div class="w-full rounded-t" style="height: {{ $h }}%; background: linear-gradient(to top, color-mix(in oklab, var(--c-cobalt) 30%, transparent), var(--c-jade))"></div>
                @endforeach
            </div>
        </div>
        <div class="glass-md glass-hover flex flex-col gap-3 p-6">
            <h3 class="t-h3" style="font-size: var(--fs-body)">Kartu stok per SKU</h3>
            <p class="t-body" style="font-size: var(--fs-body-s)">Mutasi lengkap satu SKU dengan saldo berjalan — siap dipakai untuk audit dan rekonsiliasi.</p>
            <table class="mt-1 w-full text-[12px]">
                <thead>
                    <tr class="text-left text-slate">
                        <th class="pb-2 font-medium">Tgl</th><th class="pb-2 font-medium">Keterangan</th><th class="pb-2 text-right font-medium">Saldo</th>
                    </tr>
                </thead>
                <tbody class="text-mist">
                    @foreach ([['15/8', 'Keluar 180', '1.060'], ['17/8', 'Pindah ke A3', '1.060'], ['18/8', 'Masuk 400', '1.460']] as [$t, $k, $s])
                        <tr style="border-top: 1px solid color-mix(in oklab, var(--c-frost) 10%, transparent)">
                            <td class="py-2">{{ $t }}</td><td class="py-2">{{ $k }}</td>
                            <td class="py-2 text-right tabular-nums font-semibold text-frost">{{ $s }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>

{{-- ── Cara kerja ───────────────────────────────────────────────────── --}}
<section id="cara" class="relative mx-auto grid max-w-[1080px] items-center gap-8 px-6 pt-[72px] lg:grid-cols-2">
    <div class="flex flex-col gap-3.5">
        <span class="eyebrow">Cara kerja</span>
        <h2 class="t-h2 max-w-[20ch]">Terhubung hari ini, tertata besok pagi</h2>
        <p class="t-body max-w-[52ch]" style="font-size: var(--fs-body)">
            Hubungkan sumber data yang sudah Anda punya — master rak, transaksi gudang, lembar kerja opname.
            LogistikAI menormalkan formatnya dan mulai menghitung pola pergerakan sejak hari pertama.
        </p>
        <div class="flex flex-wrap gap-2 pt-1">
            <span class="tag">Master rak</span>
            <span class="tag">Sistem gudang</span>
            <span class="tag">ERP &amp; CSV</span>
        </div>
    </div>
    <div class="glass-md flex flex-col gap-4 p-6">
        @foreach ([
            ['01', 'Petakan gudang', 'Daftarkan zona, rak, dan kapasitas — atau impor dari data master.'],
            ['02', 'Tinjau garis dasar', 'Model menyusun pola pergerakan barang Anda dalam 24 jam.'],
            ['03', 'Terapkan usulan', 'Setujui pemindahan rak langsung dari panel operasi.'],
        ] as $i => [$no, $judul, $isi])
            <div class="flex items-start gap-3 {{ $i < 2 ? 'border-b pb-4' : '' }}" @if ($i < 2) style="border-color: var(--glass-edge)" @endif>
                <span class="w-6 shrink-0 text-slate" style="font-family: var(--font-mono); font-size: 12px">{{ $no }}</span>
                <div>
                    <div class="text-[14px] font-semibold text-frost">{{ $judul }}</div>
                    <div class="text-[13px] leading-relaxed text-mist">{{ $isi }}</div>
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- ── Kutipan ──────────────────────────────────────────────────────── --}}
<section class="relative mx-auto max-w-[1080px] px-6 pt-[72px]">
    <div class="glass-md flex flex-col items-center gap-4 p-10 text-center">
        <p class="t-quote max-w-[34ch]">Kami memangkas 41 menit rata-rata waktu pengambilan tanpa menambah satu pun rak baru.</p>
        <div class="footnote" style="letter-spacing: 0.12em">Kepala gudang · perusahaan distribusi nasional</div>
    </div>
</section>

{{-- ── Penutup ──────────────────────────────────────────────────────── --}}
<section id="mulai" class="relative mx-auto max-w-[1080px] px-6 pt-[72px] pb-[88px]">
    <div class="glass-lg glass--rim flex flex-col items-center gap-4 px-10 py-12 text-center">
        <h2 class="t-cta max-w-[18ch]">Mulai dari satu rak, bukan satu proyek</h2>
        <p class="t-body max-w-[54ch]" style="font-size: var(--fs-body)">Uji coba 14 hari penuh untuk seluruh tim gudang Anda. Tanpa biaya pemasangan, tanpa kontrak minimum.</p>
        <form class="flex w-full max-w-md flex-col gap-2.5 pt-2 sm:flex-row" data-demo-form>
            <label for="email" class="sr-only">Email kerja</label>
            <input id="email" name="email" type="email" required placeholder="nama@perusahaan.co.id"
                   class="h-12 w-full rounded-[10px] border px-4 text-[14px] text-frost outline-none focus:ring-2"
                   style="border-color: var(--glass-edge); background: rgba(255,255,255,.6)">
            <button type="submit" class="btn btn--primary btn--lg">Mulai uji coba</button>
        </form>
        <div class="footnote" data-demo-note>Gratis · Data Anda tidak dibagikan ke pihak ketiga</div>
    </div>
</section>

{{-- ── Footer ───────────────────────────────────────────────────────── --}}
<footer class="relative flex justify-center border-t px-6 py-7" style="border-color: var(--glass-edge)">
    <div class="flex w-full max-w-[1080px] flex-wrap items-center justify-between gap-4">
        <div class="font-bold text-frost" style="font-family: var(--font-display); font-size: 0.9375rem">LogistikAI</div>
        <div class="flex gap-[18px] text-[13px]">
            <a href="#produk" class="text-mist no-underline hover:text-frost">Produk</a>
            <a href="#laporan" class="text-mist no-underline hover:text-frost">Laporan</a>
            <a href="#mulai" class="text-mist no-underline hover:text-frost">Kontak</a>
        </div>
        <div class="footnote">© {{ date('Y') }} LogistikAI</div>
    </div>
</footer>

<script>
    // Denah 2D: warna rak per mode (kapasitas / heatmap pergerakan) + panel detail.
    (() => {
        const racks = [...document.querySelectorAll('.rack')];
        const legend = document.getElementById('legend-bar');
        const modeBtns = [...document.querySelectorAll('.mode-btn')];
        if (!racks.length) return;

        const ramp = {
            cap: ['rgba(224,232,251,.72)', 'rgba(180,199,247,.74)', 'rgba(148,175,244,.76)', 'rgba(160,140,235,.78)'], // cobalt muda → orchid
            mov: ['rgba(209,238,229,.74)', 'rgba(150,220,196,.78)', 'rgba(216,232,146,.8)', 'rgba(240,130,150,.8)'],  // jade dingin → risk panas
        };
        const color = (mode, v) => ramp[mode][Math.min(3, Math.floor(v / 25))];
        let mode = 'cap';

        const paint = () => {
            racks.forEach(r => {
                const v = +r.dataset[mode];
                r.style.backgroundColor = color(mode, v);
                r.querySelector('.rack-metric').textContent = mode === 'cap' ? v + '%' : v;
            });
            legend.style.backgroundImage = 'linear-gradient(90deg,' + ramp[mode].join(',') + ')';
            modeBtns.forEach(b => {
                const on = b.dataset.mode === mode;
                b.style.background = on ? 'var(--c-frost)' : 'transparent';
                b.style.color = on ? 'var(--c-void)' : 'var(--c-slate)';
            });
        };

        const note = d => {
            if (+d.exp > 0) return d.exp + ' batch di rak ini mendekati kedaluwarsa — prioritaskan pengeluaran (FEFO).';
            if (+d.cap > 85 && +d.mov > 70) return 'Rak padat dan sangat aktif — kandidat pemecahan stok ke rak sebelah.';
            if (+d.cap < 40) return 'Kapasitas banyak menganggur — kandidat konsolidasi agar satu bay bisa dibebaskan.';
            if (+d.mov < 25) return 'Pergerakan rendah — posisi jauh dari dok pengiriman sudah tepat.';
            return 'Penempatan sudah sesuai rekomendasi AI saat ini.';
        };

        const setField = (f, val) => document.querySelectorAll('[data-f="' + f + '"]').forEach(el => el.textContent = val);

        const select = rack => {
            const d = rack.dataset;
            racks.forEach(r => r.setAttribute('aria-pressed', String(r === rack)));
            setField('code', d.code);
            setField('zone', d.zone);
            setField('category', d.category);
            setField('sku', d.sku);
            setField('capv', d.cap + '%');
            setField('movv', d.mov + ' / 100');
            setField('note', note(d));
        };

        racks.forEach(r => r.addEventListener('click', () => select(r)));
        modeBtns.forEach(b => b.addEventListener('click', () => { mode = b.dataset.mode; paint(); }));

        paint();
        select(racks[0]);

        // ponytail: form demo belum punya backend — ganti handler ini saat endpoint siap.
        const form = document.querySelector('[data-demo-form]');
        form.addEventListener('submit', e => {
            e.preventDefault();
            form.reset();
            document.querySelector('[data-demo-note]').textContent = 'Terima kasih! Tim kami menghubungi Anda dalam 1×24 jam kerja.';
        });
    })();
</script>
</body>
</html>
