<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboundItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'outbound_id', 'product_id', 'ordered_quantity', 'picked_quantity', 'rack_slot_id', 'batch_number',
    ];
    protected $casts = [
        'ordered_quantity' => 'decimal:3', 'picked_quantity' => 'decimal:3',
    ];
    public function outbound(): BelongsTo { return $this->belongsTo(Outbound::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function rackSlot(): BelongsTo { return $this->belongsTo(RackSlot::class); }
}
