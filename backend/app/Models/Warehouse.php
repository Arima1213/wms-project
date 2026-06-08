<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'address', 'city', 'province', 'postal_code',
        'latitude', 'longitude', 'capacity_m2', 'warehouse_type',
        'pic_name', 'pic_phone', 'pic_email',
        'operating_hours', 'is_active', 'metadata',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'capacity_m2' => 'decimal:2',
        'operating_hours' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class);
    }

    public function planogram(): HasOne
    {
        return $this->hasOne(Planogram::class);
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function inbounds(): HasMany
    {
        return $this->hasMany(Inbound::class);
    }

    public function outbounds(): HasMany
    {
        return $this->hasMany(Outbound::class);
    }

    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('warehouse_type', $type);
    }
}
