<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    protected $fillable = [
        'slot_id', 'product_id', 'batch_number',
        'manufacture_date', 'expiry_date',
        'quantity', 'reserved_quantity', 'avg_cost_price',
        'lot_number', 'notes',
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'quantity' => 'decimal:4',
        'reserved_quantity' => 'decimal:4',
        'avg_cost_price' => 'decimal:2',
    ];

    public function slot(): BelongsTo
    {
        return $this->belongsTo(RackSlot::class, 'slot_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getAvailableQtyAttribute(): float
    {
        return max(0, (float) $this->quantity - (float) $this->reserved_quantity);
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date->lte(now()->addDays($days));
    }
}