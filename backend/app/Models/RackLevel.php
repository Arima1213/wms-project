<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RackLevel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'rack_id', 'level_number', 'height_cm', 'max_weight_kg', 'is_active',
    ];

    protected $casts = [
        'height_cm' => 'decimal:2',
        'max_weight_kg' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function rack(): BelongsTo
    {
        return $this->belongsTo(Rack::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(RackSlot::class)->orderBy('slot_number');
    }
}
