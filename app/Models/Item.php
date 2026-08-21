<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'rack_id', 'sku', 'name', 'category', 'quantity', 'reorder_point', 'unit', 'movement', 'expires_at', 'placement_reason'])]
class Item extends Model
{
    protected function casts(): array
    {
        return ['expires_at' => 'date'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function rack(): BelongsTo
    {
        return $this->belongsTo(Rack::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class)->orderByRaw('expires_at IS NULL, expires_at');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
