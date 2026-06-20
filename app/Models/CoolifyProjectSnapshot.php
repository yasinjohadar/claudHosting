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
        'server' => 'سيرفر كامل',
    ];

    public const STATUSES = [
        'pending' => 'معلق',
        'running' => 'قيد التنفيذ',
        'completed' => 'مكتمل',
        'failed' => 'فاشل',
        'partial' => 'جزئي',
        'cancelled' => 'ملغاة',
    ];

    public const RESTORE_STATUSES = [
        'idle' => 'لم تُستعد',
        'running' => 'قيد الاستعادة',
        'completed' => 'مكتملة',
        'failed' => 'فاشلة',
        'partial' => 'جزئية',
        'cancelled' => 'ملغاة',
    ];

    protected $fillable = [
        'uuid',
        'scope',
        'project_uuid',
        'project_name',
        'name',
        'status',
        'restore_status',
        'options',
        'started_at',
        'completed_at',
        'restore_started_at',
        'restore_completed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'restore_started_at' => 'datetime',
            'restore_completed_at' => 'datetime',
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

    public function isCancelled(): bool
    {
        return ! empty($this->options['cancelled_at'] ?? null)
            || $this->status === 'cancelled';
    }

    public function refreshStatusFromItems(): void
    {
        if ($this->isCancelled()) {
            return;
        }

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

    public function isRestoreRunning(): bool
    {
        return $this->restore_status === 'running';
    }

    public function isRestoreFinished(): bool
    {
        return in_array($this->restore_status, ['completed', 'failed', 'partial', 'cancelled'], true);
    }

    public function refreshRestoreStatusFromItems(): void
    {
        $items = $this->items()->whereNotNull('restore_status')->get();
        if ($items->isEmpty()) {
            return;
        }

        $active = $items->whereIn('restore_status', ['pending', 'running'])->count();
        $failed = $items->where('restore_status', 'failed')->count();
        $completed = $items->where('restore_status', 'completed')->count();
        $skipped = $items->where('restore_status', 'skipped')->count();

        if ($active > 0) {
            $this->update(['restore_status' => 'running']);

            return;
        }

        $this->update([
            'restore_completed_at' => now(),
            'restore_status' => match (true) {
                $failed > 0 && ($completed > 0 || $skipped > 0) => 'partial',
                $failed > 0 && $completed === 0 && $skipped === 0 => 'failed',
                default => 'completed',
            },
        ]);
    }

    public function resetRestoreStateForItems(?array $itemIds = null): void
    {
        $query = $this->items()->where('status', 'completed');
        if ($itemIds !== null && $itemIds !== []) {
            $query->whereIn('id', $itemIds);
        }

        $query->update([
            'restore_status' => 'pending',
            'restore_error' => null,
        ]);
    }
}
