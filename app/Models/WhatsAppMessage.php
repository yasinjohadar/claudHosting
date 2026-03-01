<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessage extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'direction',
        'contact_id',
        'meta_message_id',
        'type',
        'body',
        'status',
        'payload',
        'error',
    ];

    protected $casts = [
        'payload' => 'array',
        'error' => 'array',
    ];

    /**
     * Message directions
     */
    public const DIRECTION_INBOUND = 'inbound';
    public const DIRECTION_OUTBOUND = 'outbound';

    /**
     * Message statuses
     */
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_READ = 'read';
    public const STATUS_FAILED = 'failed';

    /**
     * Message types
     */
    public const TYPE_TEXT = 'text';
    public const TYPE_TEMPLATE = 'template';
    public const TYPE_IMAGE = 'image';
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_AUDIO = 'audio';
    public const TYPE_VIDEO = 'video';

    /**
     * Relationship with contact
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(WhatsAppContact::class, 'contact_id');
    }

    /**
     * Scopes
     */
    public function scopeInbound($query)
    {
        return $query->where('direction', self::DIRECTION_INBOUND);
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', self::DIRECTION_OUTBOUND);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByMetaMessageId($query, string $metaMessageId)
    {
        return $query->where('meta_message_id', $metaMessageId);
    }

    /**
     * Check if message is sent
     */
    public function isSent(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_DELIVERED, self::STATUS_READ]);
    }

    /**
     * Check if message failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
