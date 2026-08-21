<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['stocktake_id', 'item_id', 'rack_id', 'system_quantity', 'physical_quantity', 'notes'])]
class StocktakeLine extends Model
{
    public function stocktake(): BelongsTo { return $this->belongsTo(Stocktake::class); }
    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
    public function rack(): BelongsTo { return $this->belongsTo(Rack::class); }
}
