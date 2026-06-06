<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class StockMovement extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'uuid', 'type', 'reference_type', 'reference_id',
        'warehouse_id', 'to_warehouse_id', 'product_id',
        'rack_slot_id', 'to_rack_slot_id', 'quantity',
        'before_quantity', 'after_quantity', 'cost',
        'batch_number', 'manufacture_date', 'expiry_date',
        'status', 'notes', 'created_by', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2', 'before_quantity' => 'decimal:2',
            'after_quantity' => 'decimal:2', 'cost' => 'decimal:4',
            'manufacture_date' => 'date', 'expiry_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn($m) => $m->uuid = $m->uuid ?? Str::uuid());
    }

    public function getRouteKeyName(): string { return 'uuid'; }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function product() { return $this->belongsTo(Product::class); }
}