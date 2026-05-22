<?php

namespace App\Jobs;

use App\Models\CoolifyProjectSnapshot;
use App\Services\Coolify\CoolifyProjectRestoreService;
use App\Services\Coolify\CoolifySettingsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RestoreProjectSnapshotJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 7200;

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

    public function handle(CoolifyProjectRestoreService $restore): void
    {
        $snapshot = CoolifyProjectSnapshot::find($this->snapshotId);

        if (! $snapshot) {
            return;
        }

        $snapshot->update(['status' => 'running']);

        $restore->restore($snapshot, $this->itemIds, $this->options);

        $snapshot->update(['status' => 'completed', 'completed_at' => now()]);
    }
}
