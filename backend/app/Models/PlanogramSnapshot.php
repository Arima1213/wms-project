<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanogramSnapshot extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = ['planogram_id', 'version', 'canvas_data', 'created_by', 'change_summary', 'created_at'];
    protected $casts = ['canvas_data' => 'array', 'created_at' => 'datetime'];

    public function planogram(): BelongsTo { return $this->belongsTo(Planogram::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
