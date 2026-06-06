<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_id',
        'event',
        'payload',
        'response_code',
        'response_body',
        'attempt',
        'delivered_at',
        'failed_at',
        'next_retry_at',
        'error_message',
    ];

    protected function casts(): array {
        return [
            'payload' => 'array',
            'response_body' => 'array',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'next_retry_at' => 'datetime',
        ];
    }

    public function webhook() {
        return $this->belongsTo(Webhook::class);
    }
}
