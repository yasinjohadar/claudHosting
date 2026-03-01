<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupService;
use Illuminate\Console\Command;

class CleanupExpiredBackupsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:cleanup-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'حذف النسخ الاحتياطية المنتهية الصلاحية';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $this->info('بدء تنظيف النسخ الاحتياطية المنتهية الصلاحية...');

        $count = $backupService->cleanupExpiredBackups();

        if ($count > 0) {
            $this->info("تم حذف {$count} نسخة احتياطية منتهية الصلاحية.");
        } else {
            $this->info('لا توجد نسخ احتياطية منتهية الصلاحية.');
        }

        return Command::SUCCESS;
    }
}
