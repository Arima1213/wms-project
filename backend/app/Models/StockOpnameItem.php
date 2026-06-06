<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'stock_opname_id', 'product_id', 'rack_slot_id', 'batch_number',
        'system_quantity', 'counted_quantity', 'variance', 'notes',
    ];
    protected $casts = [
        'system_quantity' => 'decimal:3', 'counted_quantity' => 'decimal:3', 'variance' => 'decimal:3',
    ];
    public function stockOpname(): BelongsTo { return $this->belongsTo(StockOpname::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function rackSlot(): BelongsTo { return $this->belongsTo(RackSlot::class); }
}
