<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Zone extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'code',
        'name',
        'zone_type', // fast_moving, slow_moving, heavy, cold, hazmat
        'temperature_range',
        'humidity_range',
        'allowed_product_types',
        'description',
        'color', // for planogram
        'is_active',
    ];

    protected function casts(): array {
        return [
            'temperature_range' => 'array',
            'humidity_range' => 'array',
            'allowed_product_types' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function warehouse() {
        return $this->belongsTo(Warehouse::class);
    }

    public function racks() {
        return $this->hasMany(Rack::class);
    }
}
