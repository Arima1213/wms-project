<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanogramItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'planogram_id', 'product_id', 'bin_id', 'rack_id',
        'x', 'y', 'width', 'height', 'rotation', 'layer',
        'config', 'is_facings', 'min_facings',
    ];
    protected $casts = [
        'x' => 'float', 'y' => 'float', 'width' => 'float',
        'height' => 'float', 'rotation' => 'float', 'layer' => 'integer',
        'is_facings' => 'boolean', 'min_facings' => 'integer',
        'config' => 'array',
    ];

    public function planogram(): BelongsTo { return $this->belongsTo(Planogram::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function bin(): BelongsTo { return $this->belongsTo(Bin::class); }
    public function rack(): BelongsTo { return $this->belongsTo(Rack::class); }
}
