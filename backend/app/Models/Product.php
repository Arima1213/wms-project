<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $fillable = [
        'sku', 'barcode', 'name', 'description', 'category_id', 'unit',
        'length_cm', 'width_cm', 'height_cm', 'weight_kg',
        'min_stock', 'max_stock', 'safety_stock', 'reorder_point',
        'purchase_price', 'selling_price', 'image', 'is_active',
    ];

    protected $casts = [
        'length_cm' => 'decimal:3', 'width_cm' => 'decimal:3', 'height_cm' => 'decimal:3',
        'weight_kg' => 'decimal:3', 'min_stock' => 'decimal:3', 'max_stock' => 'decimal:3',
        'safety_stock' => 'decimal:3', 'reorder_point' => 'decimal:3',
        'purchase_price' => 'decimal:2', 'selling_price' => 'decimal:2', 'is_active' => 'boolean',
    ];

    public function category(): BelongsTo { return $this->belongsTo(ProductCategory::class); }
    public function barcodes(): HasMany { return $this->hasMany(ProductBarcode::class); }
    public function uomConversions(): HasMany { return $this->hasMany(UomConversion::class); }
    public function inventory(): HasMany { return $this->hasMany(Inventory::class); }

    public function searchableAs(): string { return 'products'; }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'category' => $this->category?->name,
        ];
    }
}
