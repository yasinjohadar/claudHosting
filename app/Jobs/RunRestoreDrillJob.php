<?php

namespace App\Jobs;

use App\Models\CoolifyProjectSnapshot;
use App\Services\Coolify\CoolifyRestoreDrillService;
use App\Services\Coolify\CoolifySettingsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunRestoreDrillJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public function __construct(public int $snapshotId)
    {
        $this->onQueue(app(CoolifySettingsService::class)->getBackupQueue());
    }

    public function handle(CoolifyRestoreDrillService $drills): void
    {
        $snapshot = CoolifyProjectSnapshot::with('items')->find($this->snapshotId);

        if (! $snapshot || $snapshot->status !== 'completed') {
            return;
        }

        $drills->runForSnapshot($snapshot);
    }
}
