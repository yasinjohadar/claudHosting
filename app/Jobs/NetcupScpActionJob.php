<?php

namespace App\Jobs;

use App\Models\VpsActionLog;
use App\Models\VpsServer;
use App\Services\Infrastructure\Netcup\NetcupTaskService;
use App\Services\Infrastructure\VpsSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NetcupScpActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $vpsServerId,
        public ?string $taskUuid,
        public ?int $actionLogId = null
    ) {}

    public function handle(NetcupTaskService $tasks, VpsSyncService $sync): void
    {
        if ($this->taskUuid === null || $this->taskUuid === '') {
            return;
        }

        $result = $tasks->waitUntilDone($this->taskUuid);

        if ($this->actionLogId) {
            VpsActionLog::query()->whereKey($this->actionLogId)->update([
                'success' => $result['success'],
                'message' => $result['message'],
            ]);
        }

        $server = VpsServer::query()->find($this->vpsServerId);
        if ($server && $result['success']) {
            $sync->refreshOne($server);
        }
    }
}
