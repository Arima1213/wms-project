<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inbound extends Model
{
    use HasFactory;

    protected $fillable = [
        'inbound_number', 'warehouse_id', 'user_id', 'status',
        'source_type', 'source_reference', 'expected_date', 'received_date', 'notes', 'metadata',
    ];

    protected $casts = ['expected_date' => 'date', 'received_date' => 'date', 'metadata' => 'array'];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(InboundItem::class); }
}
