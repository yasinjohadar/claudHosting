<?php

namespace App\Services\Coolify;

use App\Models\CoolifyProjectSnapshot;
use App\Models\CoolifyProjectSnapshotItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CoolifySnapshotCancellationService
{
    public function __construct(
        protected CoolifyBackupAuditService $audit,
        protected CoolifySettingsService $settings
    ) {}

    public function isCancelled(CoolifyProjectSnapshot $snapshot): bool
    {
        if (! empty($snapshot->options['cancelled_at'] ?? null)) {
            return true;
        }

        return Cache::get($this->cacheKey($snapshot->id), false) === true;
    }

    /**
     * @return array{cancelled_items: int, removed_jobs: int}
     */
    public function cancel(CoolifyProjectSnapshot $snapshot): array
    {
        $options = $snapshot->options ?? [];
        $options['cancelled_at'] = now()->toIso8601String();
        $options['cancelled_by'] = auth()->id();

        $snapshot->update([
            'options' => $options,
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        Cache::put($this->cacheKey($snapshot->id), true, now()->addDay());

        $cancelledItems = CoolifyProjectSnapshotItem::query()
            ->where('snapshot_id', $snapshot->id)
            ->whereIn('status', ['pending', 'running'])
            ->update([
                'status' => 'cancelled',
                'error_message' => 'أُلغيت اللقطة بطلب المستخدم',
                'completed_at' => now(),
            ]);

        $removedJobs = $this->removeQueuedJobs($snapshot->id);

        $this->audit->log(
            'snapshot_cancel',
            'project_snapshot',
            $snapshot->uuid,
            null,
            null,
            'completed',
            'إيقاف اللقطة — '.$cancelledItems.' عنصر، '.$removedJobs.' مهمة من الطابور'
        );

        return [
            'cancelled_items' => $cancelledItems,
            'removed_jobs' => $removedJobs,
        ];
    }

    public function removeQueuedJobs(int $snapshotId): int
    {
        if (config('queue.default') !== 'database') {
            return 0;
        }

        $queue = $this->settings->getBackupQueue();
        $needleItem = '"snapshotId";i:'.$snapshotId.';';
        $needleBatch = '"snapshotId";i:'.$snapshotId;

        return DB::table('jobs')
            ->where('queue', $queue)
            ->where(function ($q) use ($needleItem, $needleBatch) {
                $q->where('payload', 'like', '%'.$needleItem.'%')
                    ->orWhere('payload', 'like', '%RunProjectSnapshotJob%'.$needleBatch.'%');
            })
            ->delete();
    }

    protected function cacheKey(int $snapshotId): string
    {
        return 'coolify.snapshot.cancelled.'.$snapshotId;
    }
}
