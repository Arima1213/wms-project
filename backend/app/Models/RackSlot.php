<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RackSlot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'rack_level_id', 'slot_code', 'slot_number',
        'max_weight_kg', 'max_volume_cm3', 'slot_type',
        'is_active', 'is_reserved', 'reserved_until', 'reserved_for',
        'fixed_product_id', 'metadata',
    ];

    protected $casts = [
        'max_weight_kg' => 'decimal:2',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_reserved' => 'boolean',
        'reserved_until' => 'datetime',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(RackLevel::class, 'rack_level_id');
    }

    public function fixedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'fixed_product_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(SlotStock::class, 'slot_id');
    }

    public function currentStocks(): HasMany
    {
        return $this->hasMany(SlotStock::class, 'slot_id')->where('is_current', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEmpty($query)
    {
        return $query->whereDoesntHave('currentStocks');
    }

    public function scopeOccupied($query)
    {
        return $query->whereHas('currentStocks');
    }
}
