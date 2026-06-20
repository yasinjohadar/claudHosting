<?php

namespace App\Jobs;

use App\Models\CoolifyWordpressSite;
use App\Services\Coolify\DockerHostService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WordpressSiteDatabaseBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public function __construct(public CoolifyWordpressSite $site) {}

    public function handle(DockerHostService $dockerHost): void
    {
        $result = $dockerHost->createDatabaseBackup($this->site);
        if (! ($result['success'] ?? false)) {
            Log::warning('WordPress DB backup failed', [
                'site_uuid' => $this->site->uuid,
                'message' => $result['message'] ?? '',
            ]);
        }
    }
}
