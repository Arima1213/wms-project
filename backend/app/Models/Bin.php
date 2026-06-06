<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bin extends Model
{
    use HasFactory;

    protected $fillable = [
        'rack_id', 'code', 'level', 'position', 'bin_type',
        'max_weight', 'max_volume', 'is_active',
    ];
    protected $casts = [
        'level' => 'integer', 'position' => 'integer',
        'max_weight' => 'float', 'max_volume' => 'float', 'is_active' => 'boolean',
    ];

    public function rack(): BelongsTo { return $this->belongsTo(Rack::class); }
    public function stocks(): HasMany { return $this->hasMany(Stock::class); }
}
