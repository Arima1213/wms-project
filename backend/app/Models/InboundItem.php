<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboundItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'inbound_id', 'product_id', 'ordered_quantity', 'received_quantity',
        'batch_number', 'expiry_date', 'unit_cost', 'target_slot_code',
    ];
    protected $casts = [
        'ordered_quantity' => 'decimal:3', 'received_quantity' => 'decimal:3',
        'expiry_date' => 'date', 'unit_cost' => 'decimal:2',
    ];
    public function inbound(): BelongsTo { return $this->belongsTo(Inbound::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
