<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;
    protected $table = 'inventory';

    protected $fillable = [
        'warehouse_id', 'product_id', 'rack_slot_id',
        'batch_number', 'expiry_date', 'quantity',
        'reserved_quantity', 'available_quantity', 'unit_cost',
    ];

    protected $casts = [
        'expiry_date' => 'date', 'quantity' => 'decimal:3',
        'reserved_quantity' => 'decimal:3', 'available_quantity' => 'decimal:3', 'unit_cost' => 'decimal:2',
    ];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function rackSlot(): BelongsTo { return $this->belongsTo(RackSlot::class); }
}
