<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\AuditLog;
use App\Models\Item;
use App\Models\Rack;
use App\Models\StockMovement;
use App\Models\Stocktake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class InventoryController extends Controller
{
    public function movement(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'integer'],
            'type' => ['required', Rule::in(['in', 'out'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'batch_number' => ['nullable', 'string', 'max:80'],
            'expires_at' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);
        $item = $this->companyItem($request, $validated['item_id']);

        if ($validated['type'] === 'out' && $validated['quantity'] > $item->quantity) {
            return back()->withErrors(['inventory' => 'Stok keluar melebihi saldo barang.']);
        }

        DB::transaction(function () use ($request, $validated, $item) {
            $delta = $validated['type'] === 'in' ? $validated['quantity'] : -$validated['quantity'];
            $item->increment('quantity', $delta);
            $batch = null;
            if (filled($validated['batch_number'])) {
                $batch = Batch::firstOrCreate(
                    ['item_id' => $item->id, 'batch_number' => $validated['batch_number']],
                    ['company_id' => $item->company_id, 'expires_at' => $validated['expires_at'] ?? null, 'quantity' => 0],
                );
                $batch->increment('quantity', $delta);
            }
            StockMovement::create([
                'company_id' => $item->company_id, 'user_id' => $request->user()->id,
                'item_id' => $item->id, 'batch_id' => $batch?->id, 'type' => $validated['type'],
                'quantity' => $validated['quantity'], 'reference' => $validated['reference'] ?? null,
            ]);
            AuditLog::create(['company_id' => $item->company_id, 'user_id' => $request->user()->id, 'action' => 'stock.'.$validated['type'], 'entity_type' => Item::class, 'entity_id' => $item->id, 'metadata' => ['quantity' => $validated['quantity'], 'batch' => $validated['batch_number'] ?? null]]);
        });

        return back()->with('status', 'Mutasi stok berhasil dicatat.');
    }

    public function transfer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'integer'],
            'rack_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);
        $item = $this->companyItem($request, $validated['item_id']);
        $rack = Rack::whereKey($validated['rack_id'])->whereHas('warehouse', fn ($q) => $q->where('company_id', $request->user()->company_id))->firstOrFail();
        $from = $item->rack_id;

        if ($validated['quantity'] > $item->quantity) {
            return back()->withErrors(['inventory' => 'Jumlah transfer melebihi saldo barang.']);
        }
        if ($rack->id !== $from && $rack->usedCapacity() + $validated['quantity'] > $rack->capacity) {
            return back()->withErrors(['inventory' => 'Kapasitas rak tujuan tidak mencukupi.']);
        }

        DB::transaction(function () use ($request, $validated, $item, $rack, $from) {
            $item->update(['rack_id' => $rack->id]);
            StockMovement::create([
                'company_id' => $item->company_id, 'user_id' => $request->user()->id,
                'item_id' => $item->id, 'from_rack_id' => $from, 'to_rack_id' => $rack->id,
                'type' => 'transfer', 'quantity' => $validated['quantity'],
            ]);
            AuditLog::create(['company_id' => $item->company_id, 'user_id' => $request->user()->id, 'action' => 'stock.transfer', 'entity_type' => Item::class, 'entity_id' => $item->id, 'metadata' => ['from_rack_id' => $from, 'to_rack_id' => $rack->id, 'quantity' => $validated['quantity']]]);
        });

        return back()->with('status', 'Barang dipindahkan dan riwayatnya dicatat.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = array_map('trim', fgetcsv($handle) ?: []);
        $required = ['sku', 'name', 'category', 'quantity'];
        abort_unless(count(array_intersect($required, $header)) === count($required), 422, 'CSV wajib memiliki kolom sku,name,category,quantity.');
        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if (! filled($data['sku'] ?? null)) continue;
            Item::updateOrCreate(
                ['company_id' => $request->user()->company_id, 'sku' => trim($data['sku'])],
                ['name' => trim($data['name']), 'category' => trim($data['category']), 'quantity' => max(0, (int) $data['quantity']), 'movement' => $data['movement'] ?? 'medium', 'reorder_point' => max(0, (int) ($data['reorder_point'] ?? 0))],
            );
            $count++;
        }
        fclose($handle);
        return back()->with('status', "{$count} SKU berhasil diimpor.");
    }

    public function startStocktake(Request $request): RedirectResponse
    {
        $company = $request->user()->company;
        $stocktake = DB::transaction(function () use ($request, $company) {
            $stocktake = Stocktake::create(['company_id' => $company->id, 'user_id' => $request->user()->id]);
            $company->items()->get()->each(fn (Item $item) => $stocktake->lines()->create(['item_id' => $item->id, 'rack_id' => $item->rack_id, 'system_quantity' => $item->quantity]));
            return $stocktake;
        });
        return back()->with('status', "Sesi stok opname #{$stocktake->id} dibuat.");
    }

    public function completeStocktake(Request $request, Stocktake $stocktake): RedirectResponse
    {
        abort_unless($stocktake->company_id === $request->user()->company_id && $stocktake->status === 'open', 403);
        $validated = $request->validate(['physical' => ['required', 'array'], 'physical.*' => ['required', 'integer', 'min:0']]);
        DB::transaction(function () use ($request, $stocktake, $validated) {
            foreach ($validated['physical'] as $lineId => $quantity) {
                $line = $stocktake->lines()->whereKey($lineId)->first();
                if (!$line) continue;
                $line->update(['physical_quantity' => $quantity]);
                $line->item->update(['quantity' => $quantity]);
            }
            $stocktake->update(['status' => 'completed', 'completed_at' => now()]);
        });
        return back()->with('status', 'Stok opname selesai dan saldo diperbarui.');
    }

    private function companyItem(Request $request, int $id): Item
    {
        return Item::where('company_id', $request->user()->company_id)->whereKey($id)->firstOrFail();
    }
}
