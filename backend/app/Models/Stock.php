<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    use HasFactory;

    protected $table = 'stocks';
    protected $fillable = [
        'product_id', 'warehouse_id', 'bin_id', 'quantity',
        'reserved_quantity', 'available_quantity', 'batch_number',
        'expiry_date', 'location_code',
    ];
    protected $casts = [
        'quantity' => 'float', 'reserved_quantity' => 'float',
        'available_quantity' => 'float', 'expiry_date' => 'date',
    ];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function bin(): BelongsTo { return $this->belongsTo(Bin::class); }
}
