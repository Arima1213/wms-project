<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Rack extends Model
{
    use HasFactory;

    protected $fillable = [
        'zone_id', 'code', 'name', 'pos_x', 'pos_y',
        'width_cm', 'depth_cm', 'height_cm', 'levels',
        'columns_per_level', 'max_weight_kg', 'orientation',
    ];

    protected $casts = [
        'pos_x' => 'integer', 'pos_y' => 'integer',
        'width_cm' => 'decimal:2', 'depth_cm' => 'decimal:2', 'height_cm' => 'decimal:2',
        'max_weight_kg' => 'decimal:2', 'levels' => 'integer', 'columns_per_level' => 'integer',
    ];

    public function zone(): BelongsTo { return $this->belongsTo(WarehouseZone::class, 'zone_id'); }
    public function levels(): HasMany { return $this->hasMany(RackLevel::class); }
    public function slots(): HasManyThrough { return $this->hasManyThrough(RackSlot::class, RackLevel::class); }
}
