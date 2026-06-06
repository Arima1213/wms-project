<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UomConversion extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'from_unit', 'to_unit', 'conversion_factor'];
    protected $casts = ['conversion_factor' => 'decimal:4'];
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
