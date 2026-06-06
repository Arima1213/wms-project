<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'sku', 'barcode', 'name', 'description',
        'category_id', 'unit_id',
        'length_cm', 'width_cm', 'height_cm', 'weight_kg',
        'min_stock', 'max_stock', 'reorder_point', 'safety_stock',
        'product_type', 'track_batch', 'track_expiry',
        'hs_code', 'image_url', 'metadata', 'is_active',
    ];

    protected $casts = [
        'length_cm' => 'decimal:2',
        'width_cm' => 'decimal:2',
        'height_cm' => 'decimal:2',
        'weight_kg' => 'decimal:3',
        'min_stock' => 'decimal:4',
        'max_stock' => 'decimal:4',
        'reorder_point' => 'decimal:4',
        'safety_stock' => 'decimal:4',
        'track_batch' => 'boolean',
        'track_expiry' => 'boolean',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'unit_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(ProductBarcode::class);
    }

    public function slotStocks(): HasMany
    {
        return $this->hasMany(SlotStock::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(fn($q) => $q
            ->where('name', 'ilike', "%{$term}%")
            ->orWhere('sku', 'ilike', "%{$term}%")
            ->orWhere('barcode', 'ilike', "%{$term}%")
            ->orWhere('code', 'ilike', "%{$term}%")
        );
    }

    public function scopeLowStock($query)
    {
        // Products where total slot stock < min_stock
        return $query->where('min_stock', '>', 0)
            ->whereHas('slotStocks', function ($q) {
                $q->where('is_current', true);
            }, '<', function ($q) {
                // This would need raw SQL; simplified for now
            });
    }
}
