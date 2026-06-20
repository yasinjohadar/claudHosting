<?php

namespace App\Jobs;

use App\Models\CoolifyProjectSnapshot;
use App\Services\Coolify\CoolifySettingsService;
use App\Services\Coolify\CoolifySnapshotCancellationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunProjectSnapshotJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public function __construct(public int $snapshotId)
    {
        $this->onQueue(app(CoolifySettingsService::class)->getBackupQueue());
    }

    public function handle(CoolifySnapshotCancellationService $cancellation): void
    {
        $snapshot = CoolifyProjectSnapshot::with('items')->find($this->snapshotId);

        if (! $snapshot || $cancellation->isCancelled($snapshot)) {
            return;
        }

        $snapshot->update(['status' => 'running', 'started_at' => now()]);

        foreach ($snapshot->items as $item) {
            if ($item->status === 'completed') {
                continue;
            }

            RunProjectSnapshotItemJob::dispatch($snapshot->id, $item->id);
        }
    }
}
