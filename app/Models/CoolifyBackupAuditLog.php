<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoolifyBackupAuditLog extends Model
{
    protected $fillable = [
        'action',
        'subject_type',
        'subject_uuid',
        'resource_type',
        'resource_uuid',
        'user_id',
        'status',
        'message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
