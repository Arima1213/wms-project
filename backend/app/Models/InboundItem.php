<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboundItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inbound_id', 'product_id',
        'expected_qty', 'received_qty', 'accepted_qty', 'rejected_qty',
        'unit_cost', 'batch_number', 'manufacture_date', 'expiry_date',
        'dest_slot_id', 'status', 'notes', 'received_at',
    ];

    protected $casts = [
        'expected_qty' => 'decimal:4',
        'received_qty' => 'decimal:4',
        'accepted_qty' => 'decimal:4',
        'rejected_qty' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'received_at' => 'datetime',
    ];

    public function inbound(): BelongsTo
    {
        return $this->belongsTo(Inbound::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function destSlot(): BelongsTo
    {
        return $this->belongsTo(RackSlot::class, 'dest_slot_id');
    }
}
