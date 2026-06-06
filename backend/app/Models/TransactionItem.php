<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItem extends Model
{
    protected $fillable = [
        'transaction_id', 'product_id',
        'from_slot_id', 'to_slot_id',
        'batch_number', 'expiry_date',
        'quantity', 'uom_quantity', 'uom',
        'cost_price', 'lot_number', 'notes',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'quantity' => 'decimal:4',
        'uom_quantity' => 'decimal:4',
        'cost_price' => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(InventoryTransaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function fromSlot(): BelongsTo
    {
        return $this->belongsTo(RackSlot::class, 'from_slot_id');
    }

    public function toSlot(): BelongsTo
    {
        return $this->belongsTo(RackSlot::class, 'to_slot_id');
    }
}