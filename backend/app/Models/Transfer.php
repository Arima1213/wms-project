<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_number', 'source_warehouse_id', 'dest_warehouse_id', 'user_id',
        'status', 'reason', 'approved_at', 'approved_by', 'received_at', 'received_by', 'notes',
    ];

    protected $casts = ['approved_at' => 'datetime', 'received_at' => 'datetime'];

    public function sourceWarehouse(): BelongsTo { return $this->belongsTo(Warehouse::class, 'source_warehouse_id'); }
    public function destWarehouse(): BelongsTo { return $this->belongsTo(Warehouse::class, 'dest_warehouse_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function approvedByUser(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function receivedByUser(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
    public function items(): HasMany { return $this->hasMany(TransferItem::class); }
}
