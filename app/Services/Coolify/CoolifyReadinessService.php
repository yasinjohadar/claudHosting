<?php

namespace App\Services\Coolify;

use App\Services\CloudflareApiService;
use App\Services\CoolifyApiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class CoolifyReadinessService
{
    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifySettingsService $settings,
        protected CoolifySshExecutor $ssh,
        protected CloudflareApiService $cloudflare
    ) {}

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{ready: bool, checks: array<int, array<string, mixed>>}
     */
    public function run(array $overrides = []): array
    {
        $checks = [
            $this->checkApi(),
            $this->checkSsh($overrides['ssh_host'] ?? null),
            $this->checkServerProject($overrides),
            $this->checkCloudflare(),
            $this->checkQueue(),
        ];

        $ready = collect($checks)->every(fn (array $c) => ($c['optional'] ?? false) || ($c['ok'] ?? false));

        return ['ready' => $ready, 'checks' => $checks];
    }

    /**
     * @return array<string, mixed>
     */
    protected function checkApi(): array
    {
        $configured = $this->coolify->isConfigured();
        if (! $configured) {
            return [
                'key' => 'api',
                'label' => 'Coolify API',
                'ok' => false,
                'message' => 'لم يُضبط عنوان API أو التوكن',
                'hint' => route('admin.coolify.settings.index'),
            ];
        }

        $ok = $this->coolify->ping();

        return [
            'key' => 'api',
            'label' => 'Coolify API',
            'ok' => $ok,
            'message' => $ok ? 'الاتصال ناجح' : 'فشل ping — تحقق من URL والتوكن',
            'hint' => route('admin.coolify.settings.index'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function checkSsh(?string $hostOverride = null): array
    {
        $host = trim((string) ($hostOverride ?: $this->settings->getSshHostFallback()));
        if ($host === '') {
            return [
                'key' => 'ssh',
                'label' => 'SSH',
                'ok' => false,
                'message' => 'لم يُحدد عنوان SSH (سيرفر أو fallback)',
                'hint' => route('admin.coolify.settings.index'),
            ];
        }

        $test = $this->ssh->testConnection($host);

        return [
            'key' => 'ssh',
            'label' => 'SSH',
            'ok' => (bool) ($test['success'] ?? false),
            'message' => (string) ($test['message'] ?? '—'),
            'hint' => route('admin.coolify.settings.index'),
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function checkServerProject(array $overrides): array
    {
        $serverUuid = trim((string) ($overrides['server_uuid'] ?? $this->settings->getWordpressDefaultServerUuid()));
        $projectUuid = trim((string) ($overrides['project_uuid'] ?? $this->settings->getWordpressSharedProjectUuid()));

        if ($serverUuid === '') {
            return [
                'key' => 'server',
                'label' => 'سيرفر / مشروع',
                'ok' => false,
                'message' => 'سيرفر افتراضي غير مضبوط',
                'hint' => route('admin.coolify.settings.index'),
            ];
        }

        if (! $this->coolify->isConfigured() || ! $this->coolify->ping()) {
            return [
                'key' => 'server',
                'label' => 'سيرفر / مشروع',
                'ok' => false,
                'message' => 'يتطلب اتصال API أولاً',
                'hint' => route('admin.coolify.settings.index'),
            ];
        }

        $serverOk = (bool) ($this->coolify->getServer($serverUuid)['success'] ?? false);
        $projectOk = $projectUuid === '' || (bool) ($this->coolify->getProject($projectUuid)['success'] ?? false);

        return [
            'key' => 'server',
            'label' => 'سيرفر / مشروع',
            'ok' => $serverOk && $projectOk,
            'message' => $serverOk
                ? ($projectOk ? 'UUIDs صالحة' : 'المشروع المشترك غير موجود في Coolify')
                : 'السيرفر الافتراضي غير موجود في Coolify',
            'hint' => route('admin.coolify.servers.index'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function checkCloudflare(): array
    {
        if (! $this->settings->getWordpressCloudflareEnabled()) {
            return [
                'key' => 'cloudflare',
                'label' => 'Cloudflare',
                'ok' => true,
                'optional' => true,
                'message' => 'معطّل في إعدادات WordPress (اختياري)',
                'hint' => route('admin.cloudflare.settings.index'),
            ];
        }

        $configured = $this->cloudflare->isConfigured();
        $zone = trim($this->settings->getWordpressCloudflareZoneId());

        return [
            'key' => 'cloudflare',
            'label' => 'Cloudflare',
            'ok' => $configured && $zone !== '',
            'optional' => false,
            'message' => $configured
                ? ($zone !== '' ? 'Token و Zone ID مضبوطان' : 'Zone ID مفقود')
                : 'Cloudflare API غير مضبوط',
            'hint' => route('admin.cloudflare.settings.index'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function checkQueue(): array
    {
        $connection = config('queue.default');
        $ok = true;
        $message = "الاتصال: {$connection}";

        try {
            if ($connection === 'database') {
                DB::connection()->getPdo();
                $pending = DB::table('jobs')->count();
                $message .= " — مهام معلّقة: {$pending}";
            } elseif ($connection === 'sync') {
                $ok = false;
                $message = 'QUEUE_CONNECTION=sync — شغّل queue:work للتزويد وWP';
            }
        } catch (\Throwable $e) {
            $ok = false;
            $message = 'فشل فحص الطابور: '.$e->getMessage();
        }

        $provisionQueue = $this->settings->getWordpressProvisionQueue();
        $mgmtQueue = $this->settings->getWordpressManagementQueue();

        return [
            'key' => 'queue',
            'label' => 'Queue Worker',
            'ok' => $ok,
            'optional' => false,
            'message' => $message,
            'hint' => "php artisan queue:work --queue={$provisionQueue},{$mgmtQueue},{$this->settings->getBackupQueue()}",
        ];
    }
}
