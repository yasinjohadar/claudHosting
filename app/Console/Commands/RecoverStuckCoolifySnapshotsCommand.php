<?php

namespace App\Console\Commands;

use App\Models\CoolifyProjectSnapshot;
use App\Services\Coolify\CoolifySnapshotStuckRecoveryService;
use Illuminate\Console\Command;

class RecoverStuckCoolifySnapshotsCommand extends Command
{
    protected $signature = 'coolify:recover-stuck-snapshots
                            {--minutes=45 : اعتبار العنصر عالقاً بعد هذه الدقائق}
                            {--uuid= : UUID لقطة محددة فقط}';

    protected $description = 'إعادة جدولة عناصر اللقطات العالقة (running/pending)';

    public function handle(CoolifySnapshotStuckRecoveryService $recovery): int
    {
        $uuid = $this->option('uuid');

        $snapshots = CoolifyProjectSnapshot::query()
            ->when($uuid, fn ($q) => $q->where('uuid', $uuid))
            ->whereIn('status', ['pending', 'running'])
            ->with('items')
            ->get();

        $redispatched = 0;

        foreach ($snapshots as $snapshot) {
            $result = $recovery->recoverAllIncomplete($snapshot);
            $redispatched += $result['recovered'];
            foreach ($result['actions'] as $name) {
                $this->line("Queued: {$name} ({$snapshot->uuid})");
            }
        }

        if ($redispatched === 0) {
            $this->info('لا توجد عناصر عالقة.');

            return self::SUCCESS;
        }

        $this->info("تم إرسال {$redispatched} مهمة. شغّل: php artisan queue:work --queue=coolify-backups");

        return self::SUCCESS;
    }
}
