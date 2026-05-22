<?php

namespace App\Services\Coolify;

use App\Jobs\RunProjectSnapshotJob;
use App\Models\CoolifyActivityLog;
use App\Models\CoolifyProjectSnapshot;
use App\Models\CoolifySnapshotSchedule;
use App\Services\CoolifyApiService;
use Illuminate\Support\Facades\Auth;

class CoolifyScheduledSnapshotService
{
    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifyProjectBackupPlanner $planner,
        protected CoolifyProjectSnapshotService $snapshots,
        protected CoolifySettingsService $settings
    ) {}

    public function runDue(): int
    {
        $due = CoolifySnapshotSchedule::query()
            ->where('enabled', true)
            ->where(function ($q) {
                $q->whereNull('next_run_at')->orWhere('next_run_at', '<=', now());
            })
            ->get();

        $count = 0;
        foreach ($due as $schedule) {
            if ($this->runSchedule($schedule)) {
                $count++;
            }
        }

        return $count;
    }

    public function runSchedule(CoolifySnapshotSchedule $schedule): bool
    {
        if (! $this->coolify->isConfigured() || ! $this->coolify->ping()) {
            $this->logFailure($schedule, 'Coolify API غير متصل');

            return false;
        }

        $readiness = $this->settings->getSnapshotReadiness();
        if (! $readiness['ready']) {
            $this->logFailure($schedule, 'إعدادات لقطة S3 غير جاهزة');

            return false;
        }

        $plan = $this->planner->buildPlan([
            'scope' => 'single_project',
            'project_uuid' => $schedule->project_uuid,
            'include_databases' => $schedule->options['include_databases'] ?? true,
            'include_applications' => $schedule->options['include_applications'] ?? true,
            'include_services' => $schedule->options['include_services'] ?? true,
        ]);
        if ($plan === []) {
            $this->logFailure($schedule, 'لا موارد في المشروع للنسخ');

            return false;
        }

        $snapshot = $this->snapshots->createFromPlan($plan, [
            'scope' => 'single_project',
            'project_uuid' => $schedule->project_uuid,
            'project_name' => $schedule->project_name,
            'name' => ($schedule->name ?: 'مجدول').' — '.now()->format('Y-m-d H:i'),
            'options' => array_merge([
                'frequency' => $schedule->frequency,
                'save_s3' => true,
                'scheduled' => true,
                'schedule_uuid' => $schedule->uuid,
            ], $schedule->options ?? []),
        ]);

        RunProjectSnapshotJob::dispatch($snapshot->id);

        $schedule->update([
            'last_run_at' => now(),
            'next_run_at' => $this->nextRun($schedule->frequency),
        ]);

        return true;
    }

    protected function nextRun(string $frequency): \Illuminate\Support\Carbon
    {
        return match ($frequency) {
            'hourly' => now()->addHour(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            default => now()->addDay(),
        };
    }

    protected function logFailure(CoolifySnapshotSchedule $schedule, string $message): void
    {
        try {
            CoolifyActivityLog::create([
                'action' => 'snapshot_schedule_failed',
                'resource_type' => 'snapshot_schedule',
                'resource_uuid' => $schedule->uuid,
                'resource_name' => $schedule->name,
                'message' => $message,
                'user_id' => Auth::id(),
            ]);
        } catch (\Throwable) {
            // table may be missing
        }
    }
}
