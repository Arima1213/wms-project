<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'secret',
        'events',
        'is_active',
        'headers',
        'created_by',
    ];

    protected function casts(): array {
        return [
            'events' => 'array',
            'headers' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveries() {
        return $this->hasMany(WebhookDelivery::class);
    }
}
