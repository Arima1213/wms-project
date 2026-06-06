<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ReturnItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['uuid', 'return_id', 'product_id', 'quantity', 'condition', 'resolution', 'refund_amount', 'notes'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'refund_amount' => 'decimal:2'];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn($m) => $m->uuid = $m->uuid ?? Str::uuid());
    }

    public function returnModel() { return $this->belongsTo(Return::class); }
    public function product() { return $this->belongsTo(Product::class); }
}