<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RackLevel extends Model
{
    use HasFactory;

    protected $fillable = ['rack_id', 'level_number', 'height_cm', 'max_weight_kg'];
    protected $casts = [
        'level_number' => 'integer', 'height_cm' => 'decimal:2', 'max_weight_kg' => 'decimal:2',
    ];

    public function rack(): BelongsTo { return $this->belongsTo(Rack::class); }
    public function slots(): HasMany { return $this->hasMany(RackSlot::class); }
}
