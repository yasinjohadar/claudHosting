<?php

namespace App\Console\Commands;

use App\Jobs\RecordVpsMetricsSnapshotJob;
use App\Models\VpsServer;
use Illuminate\Console\Command;

class RecordVpsMetricsSnapshotsCommand extends Command
{
    protected $signature = 'infrastructure:record-vps-metrics';

    protected $description = 'Queue metric snapshots for running VPS servers with an IP';

    public function handle(): int
    {
        $servers = VpsServer::query()
            ->where('status', 'running')
            ->whereNotNull('ip')
            ->where('ip', '!=', '')
            ->pluck('id');

        foreach ($servers as $id) {
            RecordVpsMetricsSnapshotJob::dispatch((int) $id);
        }

        $this->info('Queued '.$servers->count().' VPS metric snapshot(s).');

        return self::SUCCESS;
    }
}
