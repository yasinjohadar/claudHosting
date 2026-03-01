<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppWebhookEvent extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_webhook_events';

    protected $fillable = [
        'event_id',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Scopes
     */
    public function scopeProcessed($query)
    {
        return $query->whereNotNull('processed_at');
    }

    public function scopeUnprocessed($query)
    {
        return $query->whereNull('processed_at');
    }

    /**
     * Mark event as processed
     */
    public function markAsProcessed(): void
    {
        $this->update(['processed_at' => now()]);
    }

    /**
     * Check if event is already processed
     */
    public function isProcessed(): bool
    {
        return $this->processed_at !== null;
    }
}
