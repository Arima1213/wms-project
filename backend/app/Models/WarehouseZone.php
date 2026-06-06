<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseZone extends Model
{
    use HasFactory;
    protected $table = 'warehouse_zones';

    protected $fillable = [
        'warehouse_id', 'code', 'name', 'description', 'zone_type',
        'min_temp', 'max_temp', 'min_humidity', 'max_humidity',
        'allowed_categories', 'is_active',
    ];

    protected $casts = [
        'allowed_categories' => 'array', 'is_active' => 'boolean',
        'min_temp' => 'decimal:2', 'max_temp' => 'decimal:2',
        'min_humidity' => 'decimal:2', 'max_humidity' => 'decimal:2',
    ];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function racks(): HasMany { return $this->hasMany(Rack::class); }
}
