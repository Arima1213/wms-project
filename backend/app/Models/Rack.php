<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rack extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'zone_id', 'code', 'name', 'canvas_x', 'canvas_y',
        'width_cm', 'depth_cm', 'height_cm', 'orientation',
        'max_weight_kg', 'metadata', 'is_active',
    ];

    protected $casts = [
        'canvas_x' => 'float',
        'canvas_y' => 'float',
        'width_cm' => 'decimal:2',
        'depth_cm' => 'decimal:2',
        'height_cm' => 'decimal:2',
        'max_weight_kg' => 'decimal:2',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function levels(): HasMany
    {
        return $this->hasMany(RackLevel::class)->orderBy('level_number');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
