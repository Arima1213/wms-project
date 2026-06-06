<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Planogram extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'warehouse_id', 'canvas_width', 'canvas_height', 'grid_size',
        'version', 'canvas_data', 'canvas_settings',
        'change_summary', 'created_by',
    ];

    protected $casts = [
        'canvas_data' => 'array',
        'canvas_settings' => 'array',
        'canvas_width' => 'integer',
        'canvas_height' => 'integer',
        'grid_size' => 'integer',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(PlanogramSnapshot::class);
    }
}
