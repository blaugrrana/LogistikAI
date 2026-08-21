<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Rack;
use App\Models\PlacementPlan;
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

    /** Buat rencana penempatan untuk ditinjau sebelum diterapkan. */
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

        $allowed = $racks->mapWithKeys(fn (Rack $rack) => [$rack->warehouse->code.'|'.$rack->code => $rack->id]);
        $placements = collect($plan['placements'])->map(function (array $placement) use ($allowed) {
            $key = ($placement['warehouse_code'] ?? '').'|'.($placement['rack_code'] ?? '');
            return [
                'sku' => $placement['sku'] ?? null,
                'warehouse_code' => $placement['warehouse_code'] ?? null,
                'rack_code' => $placement['rack_code'] ?? null,
                'rack_id' => $allowed->get($key),
                'reason' => $placement['reason'] ?? null,
            ];
        })->filter(fn (array $placement) => filled($placement['sku']) && $placement['rack_id'])->values()->all();

        $planRecord = PlacementPlan::create([
            'company_id' => $company->id,
            'user_id' => $request->user()->id,
            'payload' => ['placements' => $placements],
        ]);

        return back()->with('status', "Rencana AI #{$planRecord->id} siap ditinjau sebelum diterapkan.");
    }

    public function applyPlan(Request $request, PlacementPlan $placementPlan): RedirectResponse
    {
        abort_unless($placementPlan->company_id === $request->user()->company_id && $placementPlan->status === 'pending', 403);
        $items = Item::where('company_id', $placementPlan->company_id)->get()->keyBy('sku');
        $racks = Rack::whereHas('warehouse', fn ($q) => $q->where('company_id', $placementPlan->company_id))->with('warehouse')->get()->keyBy('id');
        $used = $racks->mapWithKeys(fn (Rack $rack) => [$rack->id => $rack->usedCapacity()]);
        $applied = 0;

        foreach ($placementPlan->payload['placements'] ?? [] as $placement) {
            $item = $items->get($placement['sku'] ?? null);
            $rack = $racks->get($placement['rack_id'] ?? null);
            if (!$item || !$rack || $used[$rack->id] + $item->quantity > $rack->capacity) continue;
            $item->update(['rack_id' => $rack->id, 'placement_reason' => $placement['reason'] ?? null]);
            $used[$rack->id] += $item->quantity;
            $applied++;
        }

        $placementPlan->update(['status' => 'applied', 'applied_at' => now()]);
        return back()->with('status', "{$applied} penempatan dari rencana AI diterapkan.");
    }
}
