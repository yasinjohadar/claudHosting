<?php

namespace App\Services\Coolify;

use App\Services\CoolifyApiService;

class CoolifyServiceComposeService
{
    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifySshExecutor $ssh,
        protected CoolifySettingsService $settings
    ) {}

    /**
     * @return array{success: bool, message: string, output?: string}
     */
    public function redeploy(string $serviceUuid): array
    {
        $response = $this->coolify->getService($serviceUuid);
        if (! ($response['success'] ?? false)) {
            return ['success' => false, 'message' => $response['message'] ?? 'الخدمة غير موجودة'];
        }

        $service = is_array($response['data'] ?? null) ? $response['data'] : [];
        $serverUuid = (string) ($service['server_uuid'] ?? $service['destination']['server']['uuid'] ?? '');
        $host = $this->resolveServerHost($serverUuid);
        if ($host === '') {
            return ['success' => false, 'message' => 'لا يوجد عنوان IP للسيرفر'];
        }

        $name = (string) ($service['name'] ?? $serviceUuid);
        $escaped = escapeshellarg($name);
        $cmd = "if [ -d /data/coolify/services/{$escaped} ]; then cd /data/coolify/services/{$escaped} && docker compose pull && docker compose up -d; else echo 'مسار الخدمة غير موجود'; exit 1; fi";

        $result = $this->ssh->run($host, $cmd, 900);
        if ($result['success'] ?? false) {
            return ['success' => true, 'message' => 'تم إعادة نشر compose', 'output' => $result['output'] ?? ''];
        }

        return [
            'success' => false,
            'message' => 'فشل إعادة النشر: '.($result['output'] ?? 'خطأ SSH'),
        ];
    }

    protected function resolveServerHost(string $serverUuid): string
    {
        if ($serverUuid !== '') {
            $res = $this->coolify->getServer($serverUuid);
            if ($res['success'] ?? false) {
                $server = is_array($res['data'] ?? null) ? $res['data'] : [];
                $ip = trim((string) ($server['ip'] ?? $server['host'] ?? ''));
                if ($ip !== '') {
                    return $ip;
                }
            }
        }

        return trim($this->settings->getSshHostFallback());
    }
}
