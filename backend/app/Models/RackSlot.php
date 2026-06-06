<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RackSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'rack_level_id', 'slot_code', 'column_number',
        'width_cm', 'depth_cm', 'height_cm', 'max_weight_kg',
        'slot_type', 'status',
    ];

    protected $casts = [
        'column_number' => 'integer',
        'width_cm' => 'decimal:2', 'depth_cm' => 'decimal:2', 'height_cm' => 'decimal:2',
        'max_weight_kg' => 'decimal:2',
    ];

    public function rackLevel(): BelongsTo { return $this->belongsTo(RackLevel::class); }
    public function inventory(): HasMany { return $this->hasMany(Inventory::class); }

    public function getRackAttribute(): ?Rack
    {
        return $this->rackLevel?->rack;
    }
}
