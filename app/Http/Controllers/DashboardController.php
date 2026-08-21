<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Warehouse;
use App\Models\PlacementPlan;
use App\Models\Stocktake;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, GeminiService $gemini): View
    {
        $company = $request->user()->company;
        $tab = $request->query('tab') === 'akun' ? 'akun' : 'barang';

        $warehouses = Warehouse::where('company_id', $company->id)
            ->with(['racks.items'])
            ->orderBy('code')
            ->get();

        $items = Item::where('company_id', $company->id)
            ->with('rack.warehouse')
            ->orderBy('sku')
            ->get();

        $racks = $warehouses->flatMap->racks;

        return view('dashboard', [
            'tab' => $tab,
            'warehouses' => $warehouses,
            'items' => $items,
            'rackCount' => $racks->count(),
            'placedCount' => $items->whereNotNull('rack_id')->count(),
            'unplacedCount' => $items->whereNull('rack_id')->count(),
            'totalCapacity' => $racks->sum('capacity'),
            'usedCapacity' => $items->whereNotNull('rack_id')->sum('quantity'),
            'aiReady' => $gemini->isConfigured(),
            'pendingPlans' => PlacementPlan::where('company_id', $company->id)->where('status', 'pending')->latest()->get(),
            'lowStockItems' => $items->filter(fn (Item $item) => $item->reorder_point > 0 && $item->quantity <= $item->reorder_point)->values(),
            'expiringItems' => $items->filter(fn (Item $item) => $item->expires_at && $item->expires_at->lte(now()->addDays(30)))->values(),
            'openStocktake' => Stocktake::where('company_id', $company->id)->where('status', 'open')->with('lines.item')->latest()->first(),
        ]);
    }
}
