<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Pembungkus tipis Gemini API (generateContent) untuk dua tugas:
 *  1. menyusun struktur gudang + rak dari input jumlah milik pengguna
 *  2. menempatkan barang ke rak berdasarkan kategori, pergerakan, dan kapasitas
 *
 * Jawaban selalu diminta dalam JSON (responseMimeType) supaya bisa langsung
 * di-decode tanpa membersihkan teks bebas.
 */
class GeminiService
{
    public function __construct(
        private ?string $key = null,
        private ?string $model = null,
    ) {
        $this->key ??= config('services.gemini.key');
        $this->model ??= config('services.gemini.model');
    }

    public function isConfigured(): bool
    {
        return filled($this->key);
    }

    /**
     * Tafsirkan satu pesan bebas dari pengguna menjadi isian setup.
     *
     * Nilai null berarti "tidak disebut di pesan ini" — pemanggil mempertahankan
     * nilai lama. Teks acak membuat semua nilai null tetapi 'reply' tetap
     * menanggapi pesan itu lalu menanyakan isian yang masih kosong.
     *
     * @param  array{warehouse_count:?int, rack_per_warehouse:?int, categories:?string}  $state
     * @return array{warehouse_count:?int, rack_per_warehouse:?int, categories:?string, reset:bool, reply:string}
     */
    public function interpretSetupMessage(array $state, string $message): array
    {
        $stateJson = json_encode($state, JSON_UNESCAPED_UNICODE);
        $message = trim($message);

        $prompt = <<<TXT
        Anda asisten penataan gudang yang ramah dan ringkas. Anda sedang mengumpulkan tiga informasi:
        - warehouse_count: jumlah gudang, bilangan bulat 1 sampai 10
        - rack_counts: jumlah rak untuk SETIAP gudang, berupa daftar angka berurutan
          (gudang ke-1, ke-2, dan seterusnya). Tiap angka bilangan bulat 1 sampai 24.
        - categories: daftar kategori barang, satu teks dipisah koma

        Isian yang sudah terkumpul (null berarti masih kosong):
        {$stateJson}

        Pesan pengguna:
        "{$message}"

        Aturan penafsiran:
        - Pahami bahasa alami. Contoh: "dua gudang saja" -> warehouse_count 2.
          "isinya minuman sama snack" -> categories "Minuman, Snack".
        - Jumlah rak boleh berbeda tiap gudang. Contoh, bila ada 3 gudang:
          "gudang pertama 5 rak, kedua 3, ketiga 8" -> rack_counts [5, 3, 8].
        - Bila pengguna menyebut satu angka rak untuk semua gudang, ulangi angka itu
          sebanyak jumlah gudang. Contoh warehouse_count 3 dan "tiap gudang 4 rak" -> [4, 4, 4].
        - Bila jumlah gudang belum diketahui dan pengguna baru menyebut jumlah rak,
          isi rack_counts null lalu tanyakan dulu jumlah gudangnya.
        - Panjang rack_counts harus sama persis dengan warehouse_count. Jika tidak bisa dipastikan, isi null.
        - Satu pesan boleh mengisi beberapa nilai sekaligus.
        - Jika pengguna mengubah nilai yang sudah terisi, tulis nilai barunya.
        - Nilai yang TIDAK disebut pada pesan ini harus null. Jangan mengarang atau mengulang nilai lama.
        - Angka di luar rentang yang diizinkan: tetap null, dan sebutkan batasnya pada reply.
        - Jika pengguna minta mengulang dari awal, set reset true dan semua nilai null.
        - Jika pesan tidak berkaitan, tidak jelas, atau hanya teks acak: semua nilai null, reset false,
          dan pada reply tanggapi pesan itu sekilas dengan sopan lalu ajukan lagi pertanyaan yang masih kosong.

        Aturan reply:
        - Bahasa Indonesia, ramah, maksimal 2 kalimat.
        - Selalu tutup dengan pertanyaan untuk isian berikutnya yang masih kosong.
        - Jika seluruh isian sudah lengkap, reply cukup konfirmasi singkat tanpa pertanyaan.

        Balas HANYA JSON:
        {"warehouse_count":null,"rack_counts":null,"categories":null,"reset":false,"reply":"..."}
        TXT;

        $data = $this->generateJson($prompt);

        return [
            'warehouse_count' => is_numeric($data['warehouse_count'] ?? null) ? (int) $data['warehouse_count'] : null,
            'rack_counts' => $this->intList($data['rack_counts'] ?? null),
            'categories' => filled($data['categories'] ?? null) ? trim((string) $data['categories']) : null,
            'reset' => (bool) ($data['reset'] ?? false),
            'reply' => trim((string) ($data['reply'] ?? '')),
        ];
    }

    /**
     * Tafsirkan permintaan perubahan atas struktur gudang yang sudah ada.
     *
     * Dipakai setelah pemetaan selesai, sehingga pengguna tetap bisa menambah
     * gudang, menambah atau menghapus rak lewat percakapan yang sama.
     *
     * @param  list<array{code:string,name:string,racks:list<array{code:string,zone:?string,category:?string,capacity:int,terisi:int}>}>  $structure
     * @return array{actions:list<array<string,mixed>>, reply:string}
     */
    public function interpretStructureChange(array $structure, string $message): array
    {
        $structureJson = json_encode($structure, JSON_UNESCAPED_UNICODE);
        $message = trim($message);

        $prompt = <<<TXT
        Anda asisten penataan gudang. Struktur gudang perusahaan saat ini:
        {$structureJson}

        Pesan pengguna:
        "{$message}"

        Terjemahkan permintaan itu menjadi daftar tindakan. Jenis tindakan yang tersedia:
        - {"type":"add_warehouse","code":"GD-C","name":"...","focus":"...","racks":[{"code":"C1","zone":"Fast Moving","category":"Minuman","capacity":200}]}
        - {"type":"add_racks","warehouse":"GD-A","racks":[{"code":"A5","zone":"Medium Moving","category":"Snack","capacity":150}]}
        - {"type":"remove_rack","warehouse":"GD-A","rack":"A3"}
        - {"type":"remove_warehouse","warehouse":"GD-B"}

        Aturan:
        - Pakai HANYA kode gudang dan kode rak yang benar-benar ada untuk tindakan penghapusan.
        - Kode rak baru tidak boleh menabrak kode rak yang sudah ada di gudang tersebut; lanjutkan penomorannya.
        - Kode gudang baru juga harus unik terhadap daftar di atas.
        - zone diisi salah satu: "Fast Moving", "Medium Moving", atau "Slow Moving".
        - capacity bilangan bulat 50 sampai 500.
        - Jumlah rak tiap gudang maksimal 24, jumlah gudang maksimal 10.
        - Jika pesan tidak meminta perubahan apa pun, atau maksudnya tidak jelas, atau hanya teks acak:
          kembalikan actions kosong dan pada reply tanggapi pesan itu sekilas lalu tawarkan bantuan
          perubahan yang bisa dilakukan.
        - Jangan menghapus apa pun kecuali diminta jelas oleh pengguna.

        Aturan reply: bahasa Indonesia, ramah, maksimal 2 kalimat, dan sebutkan perubahan yang akan dilakukan.

        Balas HANYA JSON: {"actions":[],"reply":"..."}
        TXT;

        $data = $this->generateJson($prompt);

        return [
            'actions' => is_array($data['actions'] ?? null) ? array_values($data['actions']) : [],
            'reply' => trim((string) ($data['reply'] ?? '')),
        ];
    }

    /**
     * Ubah nilai apa pun dari model menjadi daftar bilangan bulat, atau null
     * bila bukan daftar angka yang bisa dipakai.
     *
     * @return list<int>|null
     */
    private function intList(mixed $value): ?array
    {
        if (! is_array($value) || $value === []) {
            return null;
        }

        $list = [];

        foreach ($value as $v) {
            if (! is_numeric($v)) {
                return null;
            }

            $list[] = (int) $v;
        }

        return $list;
    }

    /**
     * Susun daftar gudang beserta raknya.
     *
     * Jumlah rak boleh berbeda tiap gudang: panjang $rackCounts menentukan
     * banyaknya gudang, dan tiap nilainya jumlah rak gudang tersebut.
     *
     * @param  list<int>  $rackCounts
     * @param  list<string>  $categories
     * @return array{warehouses: list<array{code:string,name:string,focus:string,notes:string,racks:list<array{code:string,zone:string,category:string,capacity:int}>}>}
     */
    public function planWarehouses(array $rackCounts, array $categories, string $context = ''): array
    {
        $categoryList = implode(', ', $categories);
        $warehouseCount = count($rackCounts);

        $rincian = collect($rackCounts)
            ->map(fn (int $n, int $i) => 'gudang ke-'.($i + 1).': '.$n.' rak')
            ->implode('; ');

        $prompt = <<<TXT
        Anda perencana tata letak gudang. Susun {$warehouseCount} gudang dengan jumlah rak berbeda-beda
        sesuai rincian berikut, dan patuhi angkanya persis: {$rincian}

        Kategori barang yang akan disimpan: {$categoryList}
        Catatan tambahan dari pengguna: {$context}

        Aturan:
        - Kode gudang singkat huruf kapital (contoh: GD-A, GD-B). Kode rak singkat (contoh: A1, A2).
        - Sebar kategori ke rak secara merata; rak untuk kategori berfrekuensi tinggi diberi zone "Fast Moving",
          sisanya "Medium Moving" atau "Slow Moving".
        - capacity adalah angka bulat 50-500 sesuai perkiraan daya tampung rak.
        - Semua teks berbahasa Indonesia. Jangan tambahkan penjelasan di luar JSON.

        Balas HANYA JSON dengan bentuk:
        {"warehouses":[{"code":"GD-A","name":"...","focus":"...","notes":"...","racks":[{"code":"A1","zone":"Fast Moving","category":"...","capacity":120}]}]}
        TXT;

        $data = $this->generateJson($prompt);

        if (! isset($data['warehouses']) || ! is_array($data['warehouses'])) {
            throw new RuntimeException('Balasan AI tidak berisi daftar gudang yang bisa dibaca.');
        }

        return $data;
    }

    /**
     * Tentukan rak untuk tiap barang.
     *
     * @param  list<array{sku:string,name:string,category:string,quantity:int,movement:string}>  $items
     * @param  list<array{code:string,warehouse:string,zone:?string,category:?string,capacity:int,terpakai:int}>  $racks
     * @return array{placements: list<array{sku:string,rack_code:string,reason:string}>}
     */
    public function planPlacements(array $items, array $racks): array
    {
        $itemsJson = json_encode($items, JSON_UNESCAPED_UNICODE);
        $racksJson = json_encode($racks, JSON_UNESCAPED_UNICODE);

        $prompt = <<<TXT
        Anda perencana penempatan barang di gudang. Tentukan satu rak untuk setiap barang.

        Daftar rak (beserta kapasitas dan jumlah yang sudah terpakai):
        {$racksJson}

        Daftar barang yang perlu ditempatkan:
        {$itemsJson}

        Aturan urut prioritas:
        1. Cocokkan kategori barang dengan kategori rak.
        2. Barang dengan movement "fast" diletakkan di rak zone "Fast Moving" (dekat dok pengiriman).
        3. Jangan melebihi kapasitas rak: terpakai + quantity <= capacity. Jika rak yang cocok penuh,
           pilih rak lain yang masih longgar.
        4. rack_code WAJIB salah satu kode rak dari daftar di atas.
        5. reason ditulis singkat dalam bahasa Indonesia, maksimal 15 kata.

        Balas HANYA JSON dengan bentuk:
        {"placements":[{"sku":"...","rack_code":"...","reason":"..."}]}
        Sertakan seluruh SKU dari daftar barang.
        TXT;

        $data = $this->generateJson($prompt);

        if (! isset($data['placements']) || ! is_array($data['placements'])) {
            throw new RuntimeException('Balasan AI tidak berisi daftar penempatan yang bisa dibaca.');
        }

        return $data;
    }

    /** Panggil Gemini dan kembalikan hasil JSON yang sudah di-decode. */
    private function generateJson(string $prompt): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('GEMINI_API_KEY belum diisi di file .env.');
        }

        $response = Http::timeout(60)
            ->withHeaders(['x-goog-api-key' => $this->key])
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0.2,
                ],
            ]);

        if ($response->failed()) {
            $message = $response->json('error.message') ?? 'status '.$response->status();

            throw new RuntimeException('Panggilan Gemini gagal: '.$message);
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (blank($text)) {
            throw new RuntimeException('Gemini tidak mengembalikan isi jawaban.');
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Jawaban Gemini bukan JSON yang valid.');
        }

        return $decoded;
    }
}
