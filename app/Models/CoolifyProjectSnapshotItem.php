<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoolifyProjectSnapshotItem extends Model
{
    public const STRATEGIES = [
        'coolify_api' => 'نسخ Coolify API',
        'ssh_volume' => 'نسخ Volume عبر SSH',
        'manifest_only' => 'بيانات وصفية فقط',
    ];

    public const RESTORE_STATUSES = [
        'pending' => 'في الانتظار',
        'running' => 'قيد الاستعادة',
        'completed' => 'مكتملة',
        'failed' => 'فاشلة',
        'skipped' => 'متخطاة',
        'cancelled' => 'ملغاة',
    ];

    protected $fillable = [
        'snapshot_id',
        'resource_type',
        'resource_uuid',
        'resource_name',
        'project_uuid',
        'server_uuid',
        'server_host',
        'strategy',
        'status',
        'restore_status',
        'backup_path',
        'coolify_backup_config_uuid',
        'metadata',
        'error_message',
        'restore_error',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(CoolifyProjectSnapshot::class, 'snapshot_id');
    }
}
