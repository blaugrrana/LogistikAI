<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'user_id', 'item_id', 'batch_id', 'from_rack_id', 'to_rack_id', 'type', 'quantity', 'reference', 'notes'])]
class StockMovement extends Model
{
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
    public function batch(): BelongsTo { return $this->belongsTo(Batch::class); }
    public function fromRack(): BelongsTo { return $this->belongsTo(Rack::class, 'from_rack_id'); }
    public function toRack(): BelongsTo { return $this->belongsTo(Rack::class, 'to_rack_id'); }
}
