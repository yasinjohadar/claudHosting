<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\VpsServer;
use App\Services\Infrastructure\VpsActionService;
use App\Services\Infrastructure\VpsSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VpsPowerActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(
        public VpsServer $server,
        public string $action,
        public ?int $userId = null
    ) {}

    public function handle(VpsActionService $actions, VpsSyncService $sync): void
    {
        $user = $this->userId ? User::find($this->userId) : null;
        $actions->execute($this->server, $this->action, $user);

        sleep(3);
        $this->server->refresh();
        $sync->refreshOne($this->server);
    }
}
