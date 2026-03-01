<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupScheduleService;
use Illuminate\Console\Command;

class RunScheduledBackupsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'تشغيل النسخ الاحتياطية المجدولة';

    /**
     * Execute the console command.
     */
    public function handle(BackupScheduleService $scheduleService): int
    {
        $this->info('بدء تشغيل النسخ الاحتياطية المجدولة...');

        $count = $scheduleService->runScheduledBackups();

        if ($count > 0) {
            $this->info("تم تشغيل {$count} نسخة احتياطية بنجاح.");
        } else {
            $this->info('لا توجد نسخ احتياطية مجدولة للتشغيل في الوقت الحالي.');
        }

        return Command::SUCCESS;
    }
}
