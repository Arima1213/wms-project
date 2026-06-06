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
        'opname_number', 'warehouse_id', 'user_id', 'status', 'opname_type',
        'opname_date', 'submitted_at', 'approved_at', 'approved_by', 'notes',
    ];

    protected $casts = ['opname_date' => 'date', 'submitted_at' => 'datetime', 'approved_at' => 'datetime'];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function approvedByUser(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function items(): HasMany { return $this->hasMany(StockOpnameItem::class); }
}
