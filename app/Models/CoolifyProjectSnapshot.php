<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CoolifyProjectSnapshot extends Model
{
    public const SCOPES = [
        'all_projects' => 'كل المشاريع',
        'single_project' => 'مشروع واحد',
        'custom' => 'موارد مخصصة',
    ];

    public const STATUSES = [
        'pending' => 'معلق',
        'running' => 'قيد التنفيذ',
        'completed' => 'مكتمل',
        'failed' => 'فاشل',
        'partial' => 'جزئي',
    ];

    protected $fillable = [
        'uuid',
        'scope',
        'project_uuid',
        'project_name',
        'name',
        'status',
        'options',
        'started_at',
        'completed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(CoolifyProjectSnapshotItem::class, 'snapshot_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function refreshStatusFromItems(): void
    {
        $items = $this->items()->get();
        if ($items->isEmpty()) {
            return;
        }

        $failed = $items->where('status', 'failed')->count();
        $running = $items->whereIn('status', ['pending', 'running'])->count();
        $completed = $items->where('status', 'completed')->count();

        if ($running > 0) {
            $this->update(['status' => 'running']);

            return;
        }

        if ($failed === 0) {
            $this->update(['status' => 'completed', 'completed_at' => now()]);

            return;
        }

        if ($completed > 0) {
            $this->update(['status' => 'partial', 'completed_at' => now()]);
        } else {
            $this->update(['status' => 'failed', 'completed_at' => now()]);
        }
    }
}
