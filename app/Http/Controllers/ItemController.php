<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Rack;
use App\Services\GeminiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class ItemController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:50', Rule::unique('items', 'sku')->where('company_id', $request->user()->company_id)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'movement' => ['required', Rule::in(['fast', 'medium', 'slow'])],
            'expires_at' => ['nullable', 'date'],
        ]);

        Item::create($validated + ['company_id' => $request->user()->company_id]);

        return back()->with('status', 'Barang '.$validated['sku'].' ditambahkan. Jalankan spotting untuk menempatkannya.');
    }

    public function destroy(Request $request, Item $item): RedirectResponse
    {
        abort_unless($item->company_id === $request->user()->company_id, 403);

        $item->delete();

        return back()->with('status', 'Barang dihapus.');
    }

    /** Minta AI menempatkan seluruh barang ke rak yang tersedia. */
    public function spot(Request $request, GeminiService $gemini): RedirectResponse
    {
        $company = $request->user()->company;

        $racks = Rack::whereHas('warehouse', fn ($q) => $q->where('company_id', $company->id))
            ->with(['warehouse', 'items'])
            ->get();

        if ($racks->isEmpty()) {
            return back()->withErrors(['ai' => 'Belum ada rak. Susun struktur gudang terlebih dahulu.']);
        }

        $items = Item::where('company_id', $company->id)->get();

        if ($items->isEmpty()) {
            return back()->withErrors(['ai' => 'Belum ada barang yang perlu ditempatkan.']);
        }

        try {
            $plan = $gemini->planPlacements(
                $items->map(fn (Item $i) => [
                    'sku' => $i->sku,
                    'name' => $i->name,
                    'category' => $i->category,
                    'quantity' => $i->quantity,
                    'movement' => $i->movement,
                ])->all(),
                $racks->map(fn (Rack $r) => [
                    'code' => $r->code,
                    'warehouse' => $r->warehouse->code,
                    'zone' => $r->zone,
                    'category' => $r->category,
                    'capacity' => $r->capacity,
                    'terpakai' => $r->usedCapacity(),
                ])->all(),
            );
        } catch (Throwable $e) {
            return back()->withErrors(['ai' => $e->getMessage()]);
        }

        // Kode rak bisa berulang antar gudang, jadi cocokkan pada kemunculan pertama.
        $rackByCode = $racks->keyBy('code');
        $itemBySku = $items->keyBy('sku');
        $placed = 0;

        foreach ($plan['placements'] as $p) {
            $item = $itemBySku->get($p['sku'] ?? null);
            $rack = $rackByCode->get($p['rack_code'] ?? null);

            if (! $item || ! $rack) {
                continue;
            }

            $item->update([
                'rack_id' => $rack->id,
                'placement_reason' => $p['reason'] ?? null,
            ]);

            $placed++;
        }

        $skipped = $items->count() - $placed;

        return back()->with('status', $skipped === 0
            ? "AI menempatkan {$placed} barang ke rak."
            : "AI menempatkan {$placed} barang; {$skipped} barang dilewati karena rak usulan tidak dikenali.");
    }
}
