<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Rack;
use App\Models\Warehouse;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class WarehouseSetupController extends Controller
{
    private const MAX_WAREHOUSES = 10;

    private const MAX_RACKS = 24;

    /**
     * Satu giliran percakapan bubble chat.
     *
     * Selama struktur belum ada, percakapan mengumpulkan tiga isian lalu
     * menyusunnya. Setelah struktur ada, percakapan berpindah ke mode ubah:
     * menambah gudang, menambah atau menghapus rak, menghapus gudang.
     */
    public function chat(Request $request, GeminiService $gemini): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
            'state' => ['nullable', 'array'],
            'state.warehouse_count' => ['nullable', 'integer'],
            'state.rack_counts' => ['nullable', 'array', 'max:'.self::MAX_WAREHOUSES],
            'state.rack_counts.*' => ['integer'],
            'state.categories' => ['nullable', 'string', 'max:500'],
        ]);

        $company = $request->user()->company;

        return $company->warehouses()->exists()
            ? $this->handleChange($company, $validated['message'], $gemini)
            : $this->handleSetup($company, $validated, $gemini);
    }

    /** Hapus seluruh struktur gudang perusahaan supaya bisa disusun ulang. */
    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->company->warehouses()->delete();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Struktur gudang dihapus. Susun ulang lewat asisten AI.');
    }

    // ── Mode 1: penyusunan awal ──────────────────────────────────────────

    private function handleSetup(Company $company, array $validated, GeminiService $gemini): JsonResponse
    {
        $state = $this->normalizeState($validated['state'] ?? []);

        try {
            $reading = $gemini->interpretSetupMessage($state, $validated['message']);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        if ($reading['reset']) {
            $state = $this->emptyState();
        }

        if ($this->withinRange($reading['warehouse_count'], 1, self::MAX_WAREHOUSES)) {
            $state['warehouse_count'] = $reading['warehouse_count'];

            // Jumlah gudang berubah, daftar rak lama tidak lagi cocok.
            if (is_array($state['rack_counts']) && count($state['rack_counts']) !== $reading['warehouse_count']) {
                $state['rack_counts'] = null;
            }
        }

        $rackCounts = $this->validRackCounts($reading['rack_counts'], $state['warehouse_count']);

        if ($rackCounts !== null) {
            $state['rack_counts'] = $rackCounts;
        }

        $categories = $this->parseCategories((string) $reading['categories']);

        if ($categories->isNotEmpty()) {
            $state['categories'] = $categories->implode(', ');
        }

        $reply = $reading['reply'] !== '' ? $reading['reply'] : 'Baik. Ada lagi yang perlu saya catat?';

        if (! $this->isComplete($state)) {
            return response()->json(['reply' => $reply, 'state' => $state, 'done' => false]);
        }

        try {
            $plan = $gemini->planWarehouses(
                $state['rack_counts'],
                $this->parseCategories($state['categories'])->all(),
            );
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        $counts = $this->persistPlan($plan, $company);

        return response()->json([
            'reply' => "Selesai. {$counts['warehouses']} gudang dengan total {$counts['racks']} rak sudah tersimpan.",
            'state' => $state,
            'done' => true,
        ]);
    }

    // ── Mode 2: perubahan struktur yang sudah ada ────────────────────────

    private function handleChange(Company $company, string $message, GeminiService $gemini): JsonResponse
    {
        $warehouses = $company->warehouses()->with('racks.items')->orderBy('code')->get();

        $structure = $warehouses->map(fn (Warehouse $w) => [
            'code' => $w->code,
            'name' => $w->name,
            'racks' => $w->racks->map(fn (Rack $r) => [
                'code' => $r->code,
                'zone' => $r->zone,
                'category' => $r->category,
                'capacity' => $r->capacity,
                'terisi' => $r->usedCapacity(),
            ])->all(),
        ])->all();

        try {
            $reading = $gemini->interpretStructureChange($structure, $message);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        $applied = $this->applyActions($company, $warehouses, $reading['actions']);

        $reply = $reading['reply'] !== '' ? $reading['reply'] : 'Baik.';

        if ($applied['summary'] !== []) {
            $reply .= ' ('.implode(', ', $applied['summary']).')';
        }

        return response()->json([
            'reply' => $reply,
            'state' => $this->emptyState(),
            'done' => $applied['changed'],
        ]);
    }

    /**
     * Terapkan tindakan dari AI, dengan pemeriksaan ulang di sisi server:
     * kode harus benar-benar ada, tidak menabrak, dan batas jumlah dipatuhi.
     *
     * @param  Collection<int, Warehouse>  $warehouses
     * @return array{changed:bool, summary:list<string>}
     */
    private function applyActions(Company $company, Collection $warehouses, array $actions): array
    {
        $summary = [];
        $byCode = $warehouses->keyBy('code');

        DB::transaction(function () use ($company, $actions, &$summary, &$byCode) {
            foreach ($actions as $action) {
                $type = $action['type'] ?? null;

                switch ($type) {
                    case 'add_warehouse':
                        if ($company->warehouses()->count() >= self::MAX_WAREHOUSES) {
                            break;
                        }

                        $code = $this->uniqueWarehouseCode($company, (string) ($action['code'] ?? 'GD'));

                        $warehouse = Warehouse::create([
                            'company_id' => $company->id,
                            'code' => $code,
                            'name' => (string) ($action['name'] ?? 'Gudang '.$code),
                            'focus' => $action['focus'] ?? null,
                            'notes' => $action['notes'] ?? null,
                        ]);

                        $added = $this->createRacks($warehouse, $action['racks'] ?? []);
                        $byCode->put($code, $warehouse);
                        $summary[] = "gudang {$code} ditambah dengan {$added} rak";
                        break;

                    case 'add_racks':
                        $warehouse = $byCode->get($action['warehouse'] ?? null);

                        if (! $warehouse) {
                            break;
                        }

                        $added = $this->createRacks($warehouse, $action['racks'] ?? []);

                        if ($added > 0) {
                            $summary[] = "{$added} rak ditambah di {$warehouse->code}";
                        }
                        break;

                    case 'remove_rack':
                        $warehouse = $byCode->get($action['warehouse'] ?? null);
                        $rack = $warehouse?->racks()->where('code', $action['rack'] ?? '')->first();

                        if (! $rack) {
                            break;
                        }

                        // Barang di rak ini kembali berstatus belum ditempatkan.
                        $rack->delete();
                        $summary[] = "rak {$action['rack']} di {$warehouse->code} dihapus";
                        break;

                    case 'remove_warehouse':
                        $warehouse = $byCode->get($action['warehouse'] ?? null);

                        if (! $warehouse) {
                            break;
                        }

                        $warehouse->delete();
                        $byCode->forget($warehouse->code);
                        $summary[] = "gudang {$warehouse->code} dihapus";
                        break;
                }
            }
        });

        return ['changed' => $summary !== [], 'summary' => $summary];
    }

    /** @return int jumlah rak yang benar-benar dibuat */
    private function createRacks(Warehouse $warehouse, array $racks): int
    {
        $existing = $warehouse->racks()->pluck('code')->all();
        $created = 0;

        foreach ($racks as $r) {
            if (count($existing) + $created >= self::MAX_RACKS) {
                break;
            }

            $code = Str::limit((string) ($r['code'] ?? 'R'), 20, '');

            if ($code === '' || in_array($code, $existing, true)) {
                continue;
            }

            Rack::create([
                'warehouse_id' => $warehouse->id,
                'code' => $code,
                'zone' => $r['zone'] ?? null,
                'category' => $r['category'] ?? null,
                'capacity' => max(1, (int) ($r['capacity'] ?? 100)),
            ]);

            $existing[] = $code;
            $created++;
        }

        return $created;
    }

    private function uniqueWarehouseCode(Company $company, string $wanted): string
    {
        $base = Str::limit($wanted, 18, '') ?: 'GD';
        $code = $base;
        $suffix = 2;

        while ($company->warehouses()->where('code', $code)->exists()) {
            $code = $base.'-'.$suffix++;
        }

        return $code;
    }

    // ── Bantuan state ────────────────────────────────────────────────────

    /** @return array{warehouse_count:null, rack_counts:null, categories:null} */
    private function emptyState(): array
    {
        return ['warehouse_count' => null, 'rack_counts' => null, 'categories' => null];
    }

    /** Terima hanya kunci yang dikenal, supaya klien tidak bisa menitipkan data lain. */
    private function normalizeState(array $raw): array
    {
        $count = $this->withinRange($raw['warehouse_count'] ?? null, 1, self::MAX_WAREHOUSES)
            ? (int) $raw['warehouse_count']
            : null;

        return [
            'warehouse_count' => $count,
            'rack_counts' => $this->validRackCounts($raw['rack_counts'] ?? null, $count),
            'categories' => filled($raw['categories'] ?? null) ? trim((string) $raw['categories']) : null,
        ];
    }

    private function withinRange(mixed $value, int $min, int $max): bool
    {
        return is_numeric($value) && (int) $value >= $min && (int) $value <= $max;
    }

    /**
     * Daftar rak sah bila panjangnya sama dengan jumlah gudang dan tiap
     * angkanya masuk rentang.
     *
     * @return list<int>|null
     */
    private function validRackCounts(mixed $counts, ?int $warehouseCount): ?array
    {
        if (! is_array($counts) || $counts === [] || $warehouseCount === null || count($counts) !== $warehouseCount) {
            return null;
        }

        $list = [];

        foreach ($counts as $n) {
            if (! $this->withinRange($n, 1, self::MAX_RACKS)) {
                return null;
            }

            $list[] = (int) $n;
        }

        return $list;
    }

    private function isComplete(array $state): bool
    {
        return $state['warehouse_count'] !== null
            && is_array($state['rack_counts'])
            && count($state['rack_counts']) === $state['warehouse_count']
            && filled($state['categories']);
    }

    /** @return Collection<int, string> */
    private function parseCategories(string $raw): Collection
    {
        return collect(preg_split('/[,\n]+/', $raw))
            ->map(fn (string $c) => trim($c))
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * Simpan rencana AI ke database.
     *
     * @return array{warehouses:int, racks:int}
     */
    private function persistPlan(array $plan, Company $company): array
    {
        return DB::transaction(function () use ($plan, $company) {
            $warehouses = 0;
            $racks = 0;

            foreach ($plan['warehouses'] as $w) {
                $warehouse = Warehouse::create([
                    'company_id' => $company->id,
                    'code' => $this->uniqueWarehouseCode($company, (string) ($w['code'] ?? 'GD')),
                    'name' => (string) ($w['name'] ?? 'Gudang'),
                    'focus' => $w['focus'] ?? null,
                    'notes' => $w['notes'] ?? null,
                ]);
                $warehouses++;
                $racks += $this->createRacks($warehouse, $w['racks'] ?? []);
            }

            return ['warehouses' => $warehouses, 'racks' => $racks];
        });
    }
}
