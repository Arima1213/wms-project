<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductPrice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['uuid', 'product_id', 'supplier_id', 'type', 'price', 'min_qty', 'effective_date', 'end_date', 'is_active', 'notes'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2', 'min_qty' => 'decimal:2',
            'effective_date' => 'date', 'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn($m) => $m->uuid = $m->uuid ?? Str::uuid());
    }

    public function getRouteKeyName(): string { return 'uuid'; }
    public function product() { return $this->belongsTo(Product::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
}