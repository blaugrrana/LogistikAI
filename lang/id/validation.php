<?php

/**
 * Hanya memuat kaidah yang benar-benar dipakai aplikasi ini.
 * Tambahkan kunci baru saat memakai rule validasi baru.
 */
return [
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi yang Anda masukkan salah.',
    'date' => ':Attribute harus berupa tanggal yang valid.',
    'email' => 'Format :attribute tidak valid.',
    'in' => 'Pilihan :attribute tidak valid.',
    'integer' => ':Attribute harus berupa angka bulat.',
    'lowercase' => ':Attribute harus ditulis dengan huruf kecil.',
    'max' => [
        'array' => ':Attribute maksimal :max item.',
        'file' => ':Attribute maksimal :max kilobyte.',
        'numeric' => ':Attribute maksimal :max.',
        'string' => ':Attribute maksimal :max karakter.',
    ],
    'min' => [
        'array' => ':Attribute minimal :min item.',
        'file' => ':Attribute minimal :min kilobyte.',
        'numeric' => ':Attribute minimal :min.',
        'string' => ':Attribute minimal :min karakter.',
    ],
    'required' => ':Attribute wajib diisi.',
    'string' => ':Attribute harus berupa teks.',
    'unique' => ':Attribute sudah terdaftar.',

    'attributes' => [
        'company' => 'nama perusahaan',
        'name' => 'nama',
        'email' => 'alamat email',
        'password' => 'kata sandi',
        'current_password' => 'kata sandi saat ini',
        'sku' => 'SKU',
        'category' => 'kategori',
        'quantity' => 'jumlah',
        'movement' => 'frekuensi gerak',
        'expires_at' => 'tanggal kedaluwarsa',
        'warehouse_count' => 'jumlah gudang',
        'rack_per_warehouse' => 'jumlah rak per gudang',
        'categories' => 'kategori barang',
        'rack_counts' => 'jumlah rak tiap gudang',
        'message' => 'pesan',
        'context' => 'catatan tambahan',
    ],
];
