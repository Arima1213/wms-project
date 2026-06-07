<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboundItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'outbound_id', 'product_id', 'ordered_qty', 'picked_qty', 'shipped_qty',
        'unit_price', 'source_slot_id', 'batch_number', 'expiry_date', 'status', 'notes',
    ];
    protected $casts = [
        'ordered_qty' => 'decimal:4',
        'picked_qty' => 'decimal:4',
        'shipped_qty' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'expiry_date' => 'date',
    ];
    public function outbound(): BelongsTo { return $this->belongsTo(Outbound::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function sourceSlot(): BelongsTo { return $this->belongsTo(RackSlot::class, 'source_slot_id'); }
}
