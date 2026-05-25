<?php

namespace App\Console\Commands;

use App\Services\Coolify\CoolifyOperationsNotificationService;
use Illuminate\Console\Command;

class CheckCoolifyOperationsAlertsCommand extends Command
{
    protected $signature = 'coolify:check-ops-alerts';

    protected $description = 'فحص مركز عمليات Coolify وإرسال تنبيهات عند وجود مشاكل';

    public function handle(CoolifyOperationsNotificationService $notifications): int
    {
        $result = $notifications->checkAndNotify();

        if ($result['issues'] === []) {
            $this->info('لا توجد مشاكل');

            return self::SUCCESS;
        }

        $this->warn('مشاكل: '.count($result['issues']));
        foreach ($result['issues'] as $issue) {
            $this->line(' - '.$issue);
        }
        $this->info('رسائل مرسلة: '.$result['sent']);

        return self::SUCCESS;
    }
}
