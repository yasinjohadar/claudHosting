<?php

namespace App\Jobs;

use App\Models\CoolifyProjectSnapshot;
use App\Models\CoolifyProjectSnapshotItem;
use App\Services\Coolify\CoolifyProjectSnapshotService;
use App\Services\Coolify\CoolifySettingsService;
use App\Services\Coolify\CoolifySnapshotCancellationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RunProjectSnapshotItemJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public int $snapshotId,
        public int $itemId,
        public bool $force = false
    ) {
        $this->onQueue(app(CoolifySettingsService::class)->getBackupQueue());
    }

    public function handle(
        CoolifyProjectSnapshotService $service,
        CoolifySnapshotCancellationService $cancellation
    ): void {
        $snapshot = CoolifyProjectSnapshot::find($this->snapshotId);
        $item = CoolifyProjectSnapshotItem::query()
            ->where('snapshot_id', $this->snapshotId)
            ->whereKey($this->itemId)
            ->first();

        if (! $snapshot || ! $item) {
            return;
        }

        if ($cancellation->isCancelled($snapshot)) {
            if (in_array($item->status, ['pending', 'running'], true)) {
                $item->update([
                    'status' => 'cancelled',
                    'error_message' => 'أُلغيت اللقطة',
                    'completed_at' => now(),
                ]);
            }

            return;
        }

        if ($item->status === 'completed') {
            $snapshot->refreshStatusFromItems();

            return;
        }

        $staleMinutes = (int) config('coolify.snapshot_item_stale_minutes', 8);
        $cutoff = now()->subMinutes($staleMinutes);

        if ($item->status === 'running') {
            $isStale = $this->force
                || $item->started_at === null
                || $item->started_at->lt($cutoff);

            if (! $isStale) {
                return;
            }

            $item->update([
                'status' => 'pending',
                'started_at' => null,
            ]);
        }

        $lockKey = "coolify.snapshot.item.{$this->snapshotId}.{$this->itemId}";
        $lock = Cache::lock($lockKey, 600);

        if (! $lock->get()) {
            return;
        }

        try {
            $item->refresh();
            if ($item->status === 'completed') {
                return;
            }

            $service->processItem($item, $snapshot);
        } catch (\Throwable $e) {
            Log::error('Snapshot item job failed', [
                'snapshot_id' => $this->snapshotId,
                'item_id' => $this->itemId,
                'message' => $e->getMessage(),
            ]);
            $item->refresh();
            if ($item->status === 'running') {
                $item->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                ]);
            }
        } finally {
            $lock->release();
        }

        $snapshot->refresh()->refreshStatusFromItems();
    }
}
