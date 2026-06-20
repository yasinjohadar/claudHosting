<?php

namespace App\Jobs;

use App\Models\CoolifyProjectSnapshot;
use App\Services\Coolify\CoolifyPreRestoreSnapshotService;
use App\Services\Coolify\CoolifyProjectRestoreService;
use App\Services\Coolify\CoolifySettingsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RestoreProjectSnapshotJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    /**
     * @param  array<int>|null  $itemIds
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public int $snapshotId,
        public ?array $itemIds = null,
        public array $options = []
    ) {
        $this->onQueue(app(CoolifySettingsService::class)->getBackupQueue());
    }

    public function handle(
        CoolifyProjectRestoreService $restore,
        CoolifyPreRestoreSnapshotService $preRestore
    ): void {
        $snapshot = CoolifyProjectSnapshot::find($this->snapshotId);

        if (! $snapshot || $snapshot->restore_status === 'cancelled') {
            return;
        }

        if ($this->options['pre_restore_snapshot'] ?? true) {
            $preRestore->createPreRestoreSnapshot($snapshot, $this->itemIds);
        }

        $items = $restore->resolveItemsForRestore($snapshot, $this->itemIds, $this->options);

        foreach ($items as $item) {
            RestoreProjectSnapshotItemJob::dispatch($snapshot->id, $item->id, $this->options);
        }

        if ($items->isEmpty()) {
            $snapshot->update([
                'restore_status' => 'failed',
                'restore_completed_at' => now(),
            ]);
        }
    }
}
