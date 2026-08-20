# LogistikAI

Aplikasi penataan gudang berbasis AI. AI (Gemini) menyusun struktur gudang & rak dari input pengguna, menempatkan barang secara otomatis berdasarkan kategori/frekuensi/kapasitas, dan bisa diajak mengubah struktur lewat obrolan bebas.

Satu akun terikat ke satu **perusahaan**; semua akun dalam perusahaan yang sama berbagi data gudang dan barang yang sama

## Kebutuhan

- PHP 8.3+ dengan ekstensi `pdo_mysql`
- Composer
- Node.js 18+ & npm
- MySQL (lewat XAMPP)
- Kunci API Gemini — https://aistudio.google.com/apikey (gratis)

## Instalasi

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Buka **XAMPP Control Panel**, start modul **MySQL**. Buat database (nama bebas, tapi harus cocok dengan `DB_DATABASE` di `.env`):

Buka `.env`, isi kunci Gemini:

```env
GEMINI_API_KEY=isi_kunci_anda_di_sini
```

Nilai `DB_*` bawaan `.env.example` sudah cocok dengan XAMPP default (`127.0.0.1:3306`, user `root`, password kosong, database `logistikai`) — cukup ganti kalau setup Anda berbeda.

Jalankan migrasi:

```bash
php artisan migrate
```

## Menjalankan

Dua terminal, jalan bersamaan:

```bash
php artisan serve
```

```bash
npm run dev
```

Buka `http://127.0.0.1:8000`. Daftar akun baru (`/register`) — sekalian isi nama perusahaan — lalu masuk ke `/dashboard`.

Hanya ingin lihat hasil akhir tanpa hot-reload:

```bash
npm run build
php artisan serve
```

## Struktur data

- **companies** — satu perusahaan, banyak akun
- **users** — akun, terikat ke satu perusahaan (`company_id`)
- **warehouses** — gudang, milik perusahaan
- **racks** — rak per gudang, kode/zona/kategori/kapasitas bisa beda tiap rak
- **items** — barang, opsional terhubung ke satu rak

## Fitur

**Manajemen Barang** (`/dashboard`)
- Bubble chat AI di bawah nav: ceritakan bebas jumlah gudang, jumlah rak tiap gudang (boleh beda-beda), dan kategori barang — AI menafsirkan lalu menyusun strukturnya. Input tak jelas/acak ditanggapi lalu ditanya ulang, bukan ditolak diam-diam.
- Setelah struktur ada, chat yang sama dipakai untuk mengubahnya: tambah rak, tambah gudang, hapus rak/gudang — cukup minta lewat obrolan.
- Tambah barang manual (SKU, kategori, jumlah, frekuensi gerak, kedaluwarsa).
- Tombol **Jalankan spotting AI** — AI menempatkan tiap barang ke rak yang paling cocok, dengan alasan singkat per penempatan.

**Pengaturan Akun**
- Ubah profil & kata sandi, lihat jumlah akun terhubung se-perusahaan, hapus akun (data perusahaan tetap ada untuk akun lain).

## Tanpa kunci Gemini

Aplikasi tetap bisa dibuka dan login/register tetap jalan. Fitur yang butuh AI (susun gudang, ubah struktur, spotting) menampilkan catatan bahwa kunci belum diisi, dan tombol terkait nonaktif — tidak error.

## Reset database

```bash
php artisan migrate:fresh
```

Menghapus seluruh data (perusahaan, akun, gudang, barang) dan menjalankan ulang semua migrasi dari nol.
