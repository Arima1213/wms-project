<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpname extends Model
{
    use HasFactory;

    protected $fillable = [
        'opname_number', 'warehouse_id', 'zone_id', 'type', 'status',
        'start_date', 'end_date', 'notes', 'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime'
    ];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function zone(): BelongsTo { return $this->belongsTo(Zone::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function approvedByUser(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function items(): HasMany { return $this->hasMany(StockOpnameItem::class); }
}
