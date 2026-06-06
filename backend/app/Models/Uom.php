<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Uom extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['code', 'name', 'symbol', 'type', 'conversion_factor', 'base_uom_id', 'is_active'];

    protected function casts(): array {
        return ['conversion_factor' => 'decimal:10,4', 'is_active' => 'boolean'];
    }

    public function baseUom() {
        return $this->belongsTo(Uom::class, 'base_uom_id');
    }

    public function childUoms() {
        return $this->hasMany(Uom::class, 'base_uom_id');
    }
}
