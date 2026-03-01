<?php

namespace App\Jobs;

use App\Models\Backup;
use App\Services\Backup\BackupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RestoreBackupJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Backup $backup,
        public array $options = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(BackupService $backupService): void
    {
        $backupService->restoreBackup($this->backup, $this->options);
    }
}
