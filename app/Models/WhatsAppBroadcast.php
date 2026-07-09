<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppBroadcast extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_broadcasts';

    protected $fillable = [
        'message_template',
        'send_type',
        'campaign_type',
        'course_id',
        'group_id',
        'whatsapp_group_jid',
        'whatsapp_group_name',
        'meta',
        'total_recipients',
        'sent_count',
        'failed_count',
        'status',
        'created_by',
    ];

    protected $casts = [
        'total_recipients' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'meta' => 'array',
    ];

    public const CAMPAIGN_STANDARD = 'standard';

    public const CAMPAIGN_COMPARE_MISSING = 'compare_missing';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const TYPE_TEXT = 'text';

    public const TYPE_TEMPLATE = 'template';

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(WhatsAppBroadcastRecipient::class, 'broadcast_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCompareMissing($query)
    {
        return $query->where('campaign_type', self::CAMPAIGN_COMPARE_MISSING);
    }

    public function isCompareMissing(): bool
    {
        return $this->campaign_type === self::CAMPAIGN_COMPARE_MISSING;
    }
}
