<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'name', 'original_name', 'type', 'size', 'path', 'disk'];
    protected $casts = ['size' => 'integer'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
