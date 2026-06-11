<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Returns extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'returns';

    protected $fillable = [
        'uuid', 'return_number', 'warehouse_id', 'customer_id', 'supplier_id',
        'type', 'reason', 'status', 'reference_type', 'reference_id',
        'notes', 'refund_amount', 'return_date', 'processed_date', 'created_by',
        'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'return_date' => 'date', 'processed_date' => 'date',
            'refund_amount' => 'decimal:2',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn($m) => $m->uuid = $m->uuid ?? Str::uuid());
    }

    public function getRouteKeyName(): string { return 'uuid'; }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function processor() { return $this->belongsTo(User::class, 'processed_by'); }
    public function items() { return $this->hasMany(ReturnItem::class, 'return_id'); }
}