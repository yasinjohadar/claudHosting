<?php

namespace App\Jobs;

use App\Models\CoolifyWordpressSite;
use App\Services\Coolify\WordpressManagementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RunWordpressManagementJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    /**
     * @param  array<string, mixed>  $params
     */
    public function __construct(
        public int $siteId,
        public string $action,
        public array $params = [],
        public string $jobId = '',
        public ?int $userId = null
    ) {
        $this->onQueue(app(\App\Services\Coolify\CoolifySettingsService::class)->getWordpressManagementQueue());
    }

    public function handle(WordpressManagementService $management): void
    {
        $site = CoolifyWordpressSite::query()->find($this->siteId);
        if (! $site) {
            return;
        }

        $metadata = $site->metadata ?? [];
        $wpJob = $metadata['wp_job'] ?? [];
        if ($this->jobId !== '' && ($wpJob['id'] ?? '') !== $this->jobId) {
            return;
        }

        Log::info('WordPress management job started', [
            'site_id' => $this->siteId,
            'action' => $this->action,
            'job_id' => $this->jobId,
        ]);

        $result = [];
        try {
            $result = $management->runSyncAction($site, $this->action, $this->params, $this->userId);
            $status = ($result['success'] ?? false) ? 'completed' : 'failed';
            if ($this->action === 'refresh_info') {
                $output = ($result['success'] ?? false)
                    ? 'تم تحديث معلومات WordPress'
                    : (string) ($result['message'] ?? 'فشل التحديث');
            } elseif ($this->action === 'diagnose') {
                $output = (string) ($result['output'] ?? $result['message'] ?? '');
            } else {
                $output = (string) ($result['output'] ?? $result['message'] ?? '');
            }
        } catch (\Throwable $e) {
            $status = 'failed';
            $output = $e->getMessage();
            Log::error('WordPress management job failed', [
                'site_id' => $this->siteId,
                'action' => $this->action,
                'message' => $e->getMessage(),
            ]);
        }

        $site->refresh();
        $metadata = $site->metadata ?? [];
        $wpJob = $metadata['wp_job'] ?? [];
        $wpJob['status'] = $status;
        $wpJob['output'] = $output;
        $wpJob['finished_at'] = now()->toIso8601String();
        if ($status === 'completed' && isset($result['generated_password'])) {
            $wpJob['generated_password'] = $result['generated_password'];
            $wpJob['login'] = $result['login'] ?? null;
        }

        $site->update(['metadata' => array_merge($metadata, ['wp_job' => $wpJob])]);
    }
}
