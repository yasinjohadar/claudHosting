<?php

namespace App\Services\Coolify;

use App\Models\CoolifyWordpressSite;
use Illuminate\Support\Facades\DB;

class WordpressProvisioningProgress
{
    /** @var array<string, string> */
    public const STEP_LABELS = [
        'start' => 'بدء التوفير',
        'create_project' => 'تجهيز المشروع',
        'create_service' => 'إنشاء خدمة WordPress',
        'wordpress_image' => 'إعداد صورة WordPress',
        'deploy' => 'تشغيل الحاويات',
        'wait_containers' => 'انتظار MariaDB و WordPress',
        'apply_domain' => 'ربط النطاق المخصص',
        'cloudflare_skip' => 'تخطي Cloudflare',
        'cloudflare_dns' => 'DNS على Cloudflare',
        'cloudflare_ssl' => 'SSL على Cloudflare',
        'cloudflare_preset' => 'قالب أمان Cloudflare',
        'cloudflare_done' => 'اكتمال Cloudflare',
        'done' => 'اكتمال الإنشاء',
        'failed' => 'فشل',
    ];

    /** @var array<string, int> */
    protected const STEP_PERCENT = [
        'start' => 5,
        'create_project' => 12,
        'create_service' => 28,
        'wordpress_image' => 32,
        'deploy' => 42,
        'wait_containers' => 62,
        'apply_domain' => 78,
        'cloudflare_skip' => 88,
        'cloudflare_dns' => 88,
        'cloudflare_ssl' => 92,
        'cloudflare_preset' => 94,
        'cloudflare_done' => 96,
        'done' => 100,
        'failed' => 0,
    ];

    public function __construct(protected CoolifySettingsService $settings) {}

    /**
     * @return array{
     *   queue_name: string,
     *   queue_connection: string,
     *   queue_driver: string,
     *   worker_state: string,
     *   worker_label: string,
     *   pending_jobs_on_queue: int,
     *   site_job_queued: bool,
     *   site_job_failed: bool,
     *   command_hint: ?string
     * }
     */
    public function getQueueDiagnostics(CoolifyWordpressSite $site): array
    {
        $queueName = $this->settings->getWordpressProvisionQueue();
        $connection = (string) config('queue.default');
        $driver = (string) config("queue.connections.{$connection}.driver", $connection);

        $base = [
            'queue_name' => $queueName,
            'queue_connection' => $connection,
            'queue_driver' => $driver,
            'worker_state' => 'unknown',
            'worker_label' => 'غير معروف',
            'pending_jobs_on_queue' => 0,
            'site_job_queued' => false,
            'site_job_failed' => false,
            'command_hint' => null,
        ];

        $mgmt = $this->settings->getWordpressManagementQueue();
        $backup = $this->settings->getBackupQueue();
        $base['command_hint'] = 'php artisan queue:work --queue='.$queueName.','.$mgmt.','.$backup.' --tries=1 --timeout=3600';

        if ($driver === 'sync') {
            $base['worker_state'] = 'sync';
            $base['worker_label'] = 'تنفيذ فوري (sync) — لا يحتاج queue:work';

            return $base;
        }

        if ($driver !== 'database') {
            $base['worker_label'] = 'تحقق يدوياً من عامل الطابور ('.$driver.')';

            return $base;
        }

        try {
            $base['pending_jobs_on_queue'] = (int) DB::table('jobs')->where('queue', $queueName)->count();
            $base['site_job_queued'] = $this->jobExistsInTable('jobs', $site->id, $queueName);
            $base['site_job_failed'] = $this->jobExistsInTable('failed_jobs', $site->id, $queueName);
        } catch (\Throwable) {
            $base['worker_state'] = 'db_error';
            $base['worker_label'] = 'تعذر قراءة جدول jobs';

            return $base;
        }

        if (! in_array($site->status, ['pending', 'provisioning'], true)) {
            $base['worker_state'] = 'idle';
            $base['worker_label'] = 'لا يلزم عامل طابور (الموقع ليس قيد الإنشاء)';

            return $base;
        }

        $log = $site->metadata['provision_log'] ?? [];
        $minutes = $site->updated_at?->diffInMinutes(now()) ?? 0;

        if ($log !== []) {
            $base['worker_state'] = 'working';
            $base['worker_label'] = 'العامل يعمل — آخر تحديث منذ '.$minutes.' د';

            return $base;
        }

        if ($base['site_job_failed']) {
            $base['worker_state'] = 'failed_job';
            $base['worker_label'] = 'المهمة فشلت في الطابور — راجع failed_jobs أو أعد المحاولة';

            return $base;
        }

        if ($base['site_job_queued']) {
            $base['worker_state'] = 'waiting_worker';
            $base['worker_label'] = 'المهمة في الطابور — انتظر العامل أو شغّله الآن';

            return $base;
        }

        if ($minutes >= 2) {
            $base['worker_state'] = 'stalled';
            $base['worker_label'] = 'لا سجل تقدم ولا مهمة في الطابور — أعد «إعادة المحاولة»';

            return $base;
        }

        $base['worker_state'] = 'starting';
        $base['worker_label'] = 'جاري إرسال المهمة للطابور...';

        return $base;
    }

    /**
     * @return array{
     *   current_step: ?string,
     *   current_step_label: string,
     *   percent: int,
     *   steps: array<int, array{key: string, label: string, state: string}>,
     *   log_tail: array<int, array{at: string, step: string, message: string}>
     * }
     */
    public function buildProgress(CoolifyWordpressSite $site): array
    {
        $current = (string) ($site->metadata['provisioning_step'] ?? '');
        $log = $site->metadata['provision_log'] ?? [];
        $completed = collect($log)->pluck('step')->filter()->unique()->values()->all();

        $pipeline = ['start', 'create_project', 'create_service', 'deploy', 'wait_containers', 'apply_domain', 'done'];
        $steps = [];
        foreach ($pipeline as $key) {
            $state = 'pending';
            if (in_array($key, $completed, true)) {
                $state = $current === 'failed' && $key === end($pipeline) ? 'pending' : 'done';
            }
            if ($key === $current || ($current !== '' && str_starts_with($current, 'cloudflare') && $key === 'apply_domain')) {
                $state = $site->status === 'failed' ? 'failed' : 'active';
            }
            if ($current === 'failed' && ! in_array($key, $completed, true) && $state === 'pending') {
                $state = 'pending';
            }
            $steps[] = [
                'key' => $key,
                'label' => self::STEP_LABELS[$key] ?? $key,
                'state' => $state,
            ];
        }

        if ($current !== '' && str_starts_with($current, 'cloudflare')) {
            $steps[] = [
                'key' => $current,
                'label' => self::STEP_LABELS[$current] ?? 'Cloudflare',
                'state' => $site->status === 'failed' ? 'failed' : 'active',
            ];
        }

        $percent = self::STEP_PERCENT[$current] ?? 0;
        if ($current !== '' && str_starts_with($current, 'cloudflare')) {
            $percent = max($percent, 85);
        }
        if ($site->status === 'running' || $current === 'done') {
            $percent = 100;
        }

        $tail = array_slice(is_array($log) ? $log : [], -8);

        return [
            'current_step' => $current !== '' ? $current : null,
            'current_step_label' => self::STEP_LABELS[$current] ?? ($current !== '' ? $current : 'في الانتظار'),
            'percent' => min(100, max(0, $percent)),
            'steps' => $steps,
            'log_tail' => $tail,
        ];
    }

    public function staleHint(CoolifyWordpressSite $site): ?string
    {
        $diag = $this->getQueueDiagnostics($site);

        return match ($diag['worker_state']) {
            'waiting_worker' => 'الطابور: المهمة منتظرة. شغّل على السيرفر: '.$diag['command_hint'],
            'stalled' => 'الطابور: لا توجد مهمة نشطة. استخدم «إعادة المحاولة» من أعلى الصفحة.',
            'failed_job' => 'الطابور: فشلت المهمة. راجع السجل أو أعد المحاولة.',
            'db_error' => 'تعذر قراءة جدول jobs — تحقق من قاعدة البيانات.',
            'sync' => null,
            'working' => null,
            default => in_array($site->status, ['pending', 'provisioning'], true) && empty($site->metadata['provision_log'] ?? [])
                && ($site->updated_at?->diffInMinutes(now()) ?? 0) >= 3
                ? 'لم يبدأ التوفير بعد. '.$diag['command_hint']
                : null,
        };
    }

    protected function jobExistsInTable(string $table, int $siteId, string $queue): bool
    {
        if (! DB::getSchemaBuilder()->hasTable($table)) {
            return false;
        }

        $query = DB::table($table)->where('queue', $queue);
        $candidates = [
            '"siteId";i:'.$siteId,
            '"siteId":'.$siteId,
            'siteId\";i:'.$siteId,
        ];

        $query->where(function ($q) use ($candidates) {
            foreach ($candidates as $needle) {
                $q->orWhere('payload', 'like', '%'.$needle.'%');
            }
        });

        return $query->where('payload', 'like', '%ProvisionWordpressSiteJob%')->exists();
    }
}
