<?php

namespace App\Jobs;

use App\Models\WhmWordpressSite;
use App\Services\Whm\Wordpress\WhmWordpressManagementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RunWhmWordpressManagementJob implements ShouldQueue
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
        $this->onQueue((string) config('whm.wordpress_management_queue', 'default'));
    }

    public function handle(WhmWordpressManagementService $management): void
    {
        $site = WhmWordpressSite::query()->with('account')->find($this->siteId);
        if (! $site) {
            return;
        }

        $metadata = $site->metadata ?? [];
        $wpJob = $metadata['wp_job'] ?? [];
        if ($this->jobId !== '' && ($wpJob['id'] ?? '') !== $this->jobId) {
            return;
        }

        $metadata['wp_job']['status'] = 'running';
        $metadata['wp_job']['progress_label'] = 'جاري التنفيذ';
        $site->update(['metadata' => $metadata]);

        $result = [];
        try {
            $result = $management->runSyncAction($site, $this->action, $this->params, $this->userId, $this->jobId !== '' ? $this->jobId : null);
            $status = ($result['success'] ?? false) ? 'completed' : 'failed';
            $output = (string) ($result['output'] ?? $result['message'] ?? '');
        } catch (\Throwable $e) {
            $status = 'failed';
            $output = $e->getMessage();
            Log::error('WHM WordPress management job failed', [
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
        $wpJob['progress_label'] = $status === 'completed' ? 'اكتمل' : 'فشل';
        $wpJob['finished_at'] = now()->toIso8601String();
        if ($status === 'completed' && isset($result['generated_password'])) {
            $wpJob['generated_password'] = $result['generated_password'];
            $wpJob['login'] = $result['login'] ?? null;
        }
        if ($status === 'completed' && isset($result['result_file'])) {
            $wpJob['result_file'] = $result['result_file'];
        }
        if (isset($result['operation_id'])) {
            $wpJob['operation_id'] = $result['operation_id'];
        }
        $metadata['wp_job'] = $wpJob;
        $site->update(['metadata' => $metadata]);
    }
}
