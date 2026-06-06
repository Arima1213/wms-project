<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'address', 'city', 'latitude', 'longitude',
        'capacity_m2', 'type', 'pic_name', 'pic_phone', 'pic_email',
        'operating_hours', 'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:7', 'longitude' => 'decimal:7',
        'capacity_m2' => 'decimal:2', 'operating_hours' => 'array', 'is_active' => 'boolean',
    ];

    public function zones(): HasMany { return $this->hasMany(WarehouseZone::class); }
    public function planogram(): HasOne { return $this->hasOne(Planogram::class); }
    public function inventory(): HasMany { return $this->hasMany(Inventory::class); }
    public function inbounds(): HasMany { return $this->hasMany(Inbound::class); }
    public function outbounds(): HasMany { return $this->hasMany(Outbound::class); }
}
