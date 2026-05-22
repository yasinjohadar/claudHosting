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
        'backup_path',
        'coolify_backup_config_uuid',
        'metadata',
        'error_message',
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
