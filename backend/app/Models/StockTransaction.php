<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    use HasFactory;

    protected $table = 'stock_transactions';

    public $timestamps = false;

    protected $fillable = [
        'ulid',
        'transaction_type',
        'transactionable_type',
        'transactionable_id',
        'product_id',
        'source_slot_id',
        'dest_slot_id',
        'source_warehouse_id',
        'dest_warehouse_id',
        'batch_id',
        'quantity',
        'uom_id',
        'quantity_in_base_uom',
        'stock_before',
        'stock_after',
        'unit_cost',
        'total_cost',
        'reference_number',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'warehouse_id',
        'created_at',
    ];

    protected function casts(): array {
        return [
            'quantity' => 'decimal:12,4',
            'quantity_in_base_uom' => 'decimal:12,4',
            'stock_before' => 'decimal:12,4',
            'stock_after' => 'decimal:12,4',
            'unit_cost' => 'decimal:15,4',
            'total_cost' => 'decimal:15,4',
            'approved_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    // Transaction types: GR=GoodsReceived, GI=GoodsIssue, TR=Transfer, LT=Loss/Theft,
    // SO=StockOpname, ADJ+=AdjustmentPlus, ADJ-=AdjustmentMinus, RS=Reservation, RC=ReservationCancel

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function sourceSlot() {
        return $this->belongsTo(RackSlot::class, 'source_slot_id');
    }

    public function destSlot() {
        return $this->belongsTo(RackSlot::class, 'dest_slot_id');
    }

    public function sourceWarehouse() {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function destWarehouse() {
        return $this->belongsTo(Warehouse::class, 'dest_warehouse_id');
    }

    public function batch() {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver() {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function warehouse() {
        return $this->belongsTo(Warehouse::class);
    }
}
