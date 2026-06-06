<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    use HasFactory;
    public $timestamps = true;

    protected $fillable = [
        'transaction_number', 'type', 'warehouse_id', 'product_id', 'rack_slot_id',
        'batch_number', 'quantity', 'before_quantity', 'after_quantity',
        'direction', 'user_id', 'reference_type', 'reference_id', 'notes', 'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:3', 'before_quantity' => 'decimal:3', 'after_quantity' => 'decimal:3',
        'metadata' => 'array',
    ];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function rackSlot(): BelongsTo { return $this->belongsTo(RackSlot::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
