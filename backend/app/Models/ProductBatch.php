<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductBatch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'batch_number',
        'expiry_date',
        'manufacture_date',
        'origin_country',
        'cost',
        'is_active',
    ];

    protected function casts(): array {
        return [
            'expiry_date' => 'date',
            'manufacture_date' => 'date',
            'cost' => 'decimal:15,4',
            'is_active' => 'boolean',
        ];
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }
}
