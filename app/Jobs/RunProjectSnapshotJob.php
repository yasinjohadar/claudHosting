<?php

namespace App\Jobs;

use App\Models\CoolifyProjectSnapshot;
use App\Services\Coolify\CoolifyProjectSnapshotService;
use App\Services\Coolify\CoolifySettingsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RunProjectSnapshotJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 7200;

    public function __construct(public int $snapshotId)
    {
        $this->onQueue(app(CoolifySettingsService::class)->getBackupQueue());
    }

    public function handle(CoolifyProjectSnapshotService $service): void
    {
        $snapshot = CoolifyProjectSnapshot::with('items')->find($this->snapshotId);

        if (! $snapshot) {
            return;
        }

        $snapshot->update(['status' => 'running', 'started_at' => now()]);

        foreach ($snapshot->items as $item) {
            if ($item->status !== 'pending') {
                continue;
            }

            try {
                $service->processItem($item, $snapshot);
            } catch (\Throwable $e) {
                Log::error('Snapshot item failed', [
                    'snapshot_id' => $snapshot->id,
                    'item_id' => $item->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $snapshot->refreshStatusFromItems();
    }
}
