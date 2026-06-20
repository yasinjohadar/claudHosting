<?php

namespace App\Jobs;

use App\Models\VpsServer;
use App\Services\Infrastructure\VpsMetricsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordVpsMetricsSnapshotJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $vpsServerId) {}

    public function handle(VpsMetricsService $metrics): void
    {
        $server = VpsServer::query()->find($this->vpsServerId);
        if ($server === null) {
            return;
        }

        $metrics->recordSnapshot($server);
    }
}
