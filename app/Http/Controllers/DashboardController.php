<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Warehouse;
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
        ]);
    }
}
