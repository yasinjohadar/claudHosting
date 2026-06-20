<?php

namespace App\Jobs;

use App\Models\CoolifyProjectSnapshot;
use App\Models\CoolifyProjectSnapshotItem;
use App\Services\Coolify\CoolifyProjectRestoreService;
use App\Services\Coolify\CoolifySettingsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RestoreProjectSnapshotItemJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public int $snapshotId,
        public int $itemId,
        public array $options = []
    ) {
        $this->onQueue(app(CoolifySettingsService::class)->getBackupQueue());
    }

    public function handle(CoolifyProjectRestoreService $restore): void
    {
        $snapshot = CoolifyProjectSnapshot::find($this->snapshotId);
        $item = CoolifyProjectSnapshotItem::query()
            ->where('snapshot_id', $this->snapshotId)
            ->whereKey($this->itemId)
            ->first();

        if (! $snapshot || ! $item) {
            return;
        }

        if ($snapshot->restore_status === 'cancelled') {
            $item->update([
                'restore_status' => 'cancelled',
                'restore_error' => 'أُلغيت الاستعادة',
            ]);

            return;
        }

        if ($item->restore_status === 'completed' || $item->restore_status === 'skipped') {
            $restore->finalizeRestoreIfDone($snapshot->fresh());

            return;
        }

        try {
            $restore->processRestoreItem($item, $this->options);
        } catch (\Throwable $e) {
            Log::error('Restore item job failed', [
                'snapshot_id' => $this->snapshotId,
                'item_id' => $this->itemId,
                'message' => $e->getMessage(),
            ]);
            $item->refresh();
            if ($item->restore_status === 'running') {
                $item->update([
                    'restore_status' => 'failed',
                    'restore_error' => $e->getMessage(),
                ]);
            }
        }

        $restore->finalizeRestoreIfDone($snapshot->fresh());
    }
}
