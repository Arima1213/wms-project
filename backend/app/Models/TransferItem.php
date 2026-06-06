<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferItem extends Model
{
    use HasFactory;
    protected $fillable = ['transfer_id', 'product_id', 'quantity', 'batch_number', 'source_slot_id', 'dest_slot_id'];
    protected $casts = ['quantity' => 'decimal:3'];
    public function transfer(): BelongsTo { return $this->belongsTo(Transfer::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function sourceSlot(): BelongsTo { return $this->belongsTo(RackSlot::class, 'source_slot_id'); }
    public function destSlot(): BelongsTo { return $this->belongsTo(RackSlot::class, 'dest_slot_id'); }
}
