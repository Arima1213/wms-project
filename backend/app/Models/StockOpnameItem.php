<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'stock_opname_id', 'product_id', 'slot_id',
        'system_qty', 'counted_qty', 'variance', 'variance_status', 'notes',
        'counted_by', 'counted_at'
    ];
    protected $casts = [
        'system_qty' => 'decimal:4', 'counted_qty' => 'decimal:4', 'variance' => 'decimal:4',
        'counted_at' => 'datetime'
    ];
    public function stockOpname(): BelongsTo { return $this->belongsTo(StockOpname::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function slot(): BelongsTo { return $this->belongsTo(RackSlot::class, 'slot_id'); }
    public function countedByUser(): BelongsTo { return $this->belongsTo(User::class, 'counted_by'); }
}
