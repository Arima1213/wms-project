<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Planogram extends Model
{
    use HasFactory;

    protected $fillable = ['warehouse_id', 'version', 'canvas_data', 'canvas_settings', 'created_by', 'change_summary'];
    protected $casts = ['canvas_data' => 'array', 'canvas_settings' => 'array'];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function snapshots(): HasMany { return $this->hasMany(PlanogramSnapshot::class); }
}
