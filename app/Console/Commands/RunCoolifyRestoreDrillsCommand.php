<?php

namespace App\Console\Commands;

use App\Jobs\RunRestoreDrillJob;
use App\Models\CoolifyProjectSnapshot;
use Illuminate\Console\Command;

class RunCoolifyRestoreDrillsCommand extends Command
{
    protected $signature = 'coolify:run-restore-drills {--snapshot= : UUID of a specific snapshot}';

    protected $description = 'Verify latest completed snapshots are restorable (restore drill)';

    public function handle(): int
    {
        $uuid = $this->option('snapshot');

        if ($uuid) {
            $snapshot = CoolifyProjectSnapshot::where('uuid', $uuid)->first();
            if (! $snapshot) {
                $this->error('اللقطة غير موجودة');

                return self::FAILURE;
            }
            RunRestoreDrillJob::dispatch($snapshot->id);
            $this->info('تم إرسال restore drill للقطة '.$uuid);

            return self::SUCCESS;
        }

        $snapshots = CoolifyProjectSnapshot::query()
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->subDays(7))
            ->latest('completed_at')
            ->limit(5)
            ->get();

        foreach ($snapshots as $snapshot) {
            RunRestoreDrillJob::dispatch($snapshot->id);
            $this->line('Drill queued: '.$snapshot->uuid);
        }

        $this->info('تم جدولة '.$snapshots->count().' restore drill(s)');

        return self::SUCCESS;
    }
}
