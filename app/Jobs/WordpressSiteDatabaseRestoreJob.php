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

class WordpressSiteDatabaseRestoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public function __construct(public CoolifyWordpressSite $site, public string $backupPath) {}

    public function handle(DockerHostService $dockerHost): void
    {
        $result = $dockerHost->restoreDatabaseBackup($this->site, $this->backupPath);

        $this->site->mergeMetadata([
            'db_restore_status' => [
                'success' => $result['success'] ?? false,
                'message' => $result['message'] ?? null,
                'path' => $this->backupPath,
                'finished_at' => now()->toIso8601String(),
            ],
        ]);

        if (! ($result['success'] ?? false)) {
            Log::warning('WordPress DB restore failed', [
                'site_uuid' => $this->site->uuid,
                'path' => $this->backupPath,
                'message' => $result['message'] ?? '',
            ]);
        }
    }
}
