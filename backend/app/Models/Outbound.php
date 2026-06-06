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
        'outbound_number', 'warehouse_id', 'user_id', 'status',
        'destination_type', 'destination_reference', 'customer_name',
        'shipping_address', 'expected_date', 'shipped_date', 'notes',
    ];

    protected $casts = ['expected_date' => 'date', 'shipped_date' => 'date'];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(OutboundItem::class); }
}
