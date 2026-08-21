<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'item_id', 'batch_number', 'quantity', 'expires_at'])]
class Batch extends Model
{
    protected function casts(): array
    {
        return ['expires_at' => 'date'];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
    public function movements(): HasMany { return $this->hasMany(StockMovement::class); }
}
