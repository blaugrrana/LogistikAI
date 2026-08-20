<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['warehouse_id', 'code', 'zone', 'category', 'capacity'])]
class Rack extends Model
{
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /** Total kuantitas barang yang sedang menempati rak ini.
     *  Bukan load() — nama itu milik Model::load() bawaan Eloquent. */
    public function usedCapacity(): int
    {
        return (int) $this->items->sum('quantity');
    }

    /** Persentase kapasitas terpakai, dipotong di 100 supaya bar tidak meluber. */
    public function loadPercent(): int
    {
        if ($this->capacity < 1) {
            return 0;
        }

        return (int) min(100, round($this->usedCapacity() / $this->capacity * 100));
    }
}
