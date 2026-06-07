<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outbound extends Model
{
    use HasFactory;

    protected $fillable = [
        'outbound_number', 'warehouse_id', 'customer_id', 'type', 'status',
        'order_date', 'shipped_date', 'delivered_date',
        'reference_number', 'destination_name', 'destination_address',
        'shipping_method', 'tracking_number', 'shipping_cost', 'total_amount',
        'notes', 'metadata', 'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'shipped_date' => 'date',
        'delivered_date' => 'date',
        'approved_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(OutboundItem::class); }
}
