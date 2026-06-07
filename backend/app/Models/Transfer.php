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
        'transfer_number', 'source_warehouse_id', 'dest_warehouse_id', 'created_by',
        'status', 'reason', 'notes', 'expected_date', 'completed_date', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'expected_date' => 'date',
        'completed_date' => 'date',
        'approved_at' => 'datetime'
    ];

    public function sourceWarehouse(): BelongsTo { return $this->belongsTo(Warehouse::class, 'source_warehouse_id'); }
    public function destWarehouse(): BelongsTo { return $this->belongsTo(Warehouse::class, 'dest_warehouse_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function approvedByUser(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function items(): HasMany { return $this->hasMany(TransferItem::class); }
}
