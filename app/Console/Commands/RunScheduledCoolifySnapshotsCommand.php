<?php

namespace App\Console\Commands;

use App\Models\CoolifyActivityLog;
use App\Models\CoolifyProjectSnapshot;
use App\Services\Coolify\CoolifyScheduledSnapshotService;
use Illuminate\Console\Command;

class RunScheduledCoolifySnapshotsCommand extends Command
{
    protected $signature = 'coolify:run-scheduled-snapshots';

    protected $description = 'تشغيل لقطات مشروع Coolify المجدولة';

    public function handle(CoolifyScheduledSnapshotService $service): int
    {
        $count = $service->runDue();
        $this->info("تم جدولة {$count} لقطة.");

        $failed = CoolifyProjectSnapshot::query()
            ->whereIn('status', ['failed', 'partial'])
            ->where('completed_at', '>=', now()->subDay())
            ->count();

        if ($failed > 0) {
            $this->warn("لقطات فاشلة خلال 24 ساعة: {$failed}");
            try {
                CoolifyActivityLog::create([
                    'action' => 'snapshot_batch_alert',
                    'resource_type' => 'system',
                    'message' => "لقطات فاشلة: {$failed}",
                ]);
            } catch (\Throwable) {
            }
        }

        return Command::SUCCESS;
    }
}
