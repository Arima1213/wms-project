<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanogramLayout extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'canvas_width',
        'canvas_height',
        'grid_size',
        'version',
        'layout_data',
    ];

    protected function casts(): array {
        return [
            'canvas_width' => 'integer',
            'canvas_height' => 'integer',
            'grid_size' => 'integer',
            'version' => 'integer',
            'layout_data' => 'array',
        ];
    }

    public function warehouse() {
        return $this->belongsTo(Warehouse::class);
    }
}
