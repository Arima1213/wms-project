<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlotStock extends Model
{
    use HasFactory;

    protected $table = 'slot_stocks';

    protected $fillable = [
        'slot_id',
        'product_id',
        'batch_id',
        'quantity',
        'uom_id',
        'quantity_in_base_uom',
        'unit_cost',
        'total_cost',
        'expiry_date',
        'is_current',
    ];

    protected function casts(): array {
        return [
            'quantity' => 'decimal:12,4',
            'quantity_in_base_uom' => 'decimal:12,4',
            'unit_cost' => 'decimal:15,4',
            'total_cost' => 'decimal:15,4',
            'expiry_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function slot() {
        return $this->belongsTo(RackSlot::class, 'slot_id');
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function batch() {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }

    public function uom() {
        return $this->belongsTo(Uom::class);
    }
}
