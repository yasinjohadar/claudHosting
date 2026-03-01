<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppBroadcast extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_broadcasts';

    protected $fillable = [
        'message_template',
        'send_type',
        'course_id',
        'group_id',
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
    ];

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Send type constants
     */
    public const TYPE_TEXT = 'text';
    public const TYPE_TEMPLATE = 'template';

    /**
     * Relation to Course
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Relation to CourseGroup
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(CourseGroup::class, 'group_id');
    }

    /**
     * Relation to Creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scopes
     */
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
}
