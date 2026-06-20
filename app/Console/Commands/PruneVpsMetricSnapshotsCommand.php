<?php

namespace App\Console\Commands;

use App\Services\Infrastructure\VpsMetricsService;
use Illuminate\Console\Command;

class PruneVpsMetricSnapshotsCommand extends Command
{
    protected $signature = 'infrastructure:prune-vps-metrics';

    protected $description = 'Delete VPS metric snapshots older than retention period';

    public function handle(VpsMetricsService $metrics): int
    {
        $deleted = $metrics->pruneOldSnapshots();
        $this->info("Deleted {$deleted} old snapshot(s).");

        return self::SUCCESS;
    }
}
