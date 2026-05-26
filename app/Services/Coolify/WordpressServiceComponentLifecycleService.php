<?php

namespace App\Services\Coolify;

use App\Models\CoolifyWordpressSite;
use App\Services\CoolifyApiService;

class WordpressServiceComponentLifecycleService
{
    public function __construct(
        protected CoolifyApiService $coolify,
        protected WordpressCliService $cli
    ) {}

    /**
     * @return array{success: bool, message: string, output?: string}
     */
    public function restart(CoolifyWordpressSite $site, string $componentName): array
    {
        $componentName = $this->sanitizeComposeServiceName($componentName);
        if ($componentName === '') {
            return ['success' => false, 'message' => 'اسم الحاوية غير صالح'];
        }

        if (! filled($site->service_uuid)) {
            return ['success' => false, 'message' => 'لا توجد خدمة Coolify لهذا الموقع'];
        }

        $serviceResponse = $this->coolify->getService($site->service_uuid);
        if (! ($serviceResponse['success'] ?? false)) {
            return ['success' => false, 'message' => $serviceResponse['message'] ?? 'تعذّر جلب الخدمة من Coolify'];
        }

        $service = is_array($serviceResponse['data'] ?? null) ? $serviceResponse['data'] : [];
        $component = $this->findComponent($service, $componentName);

        if ($component !== null && ($component['uuid'] ?? '') !== '') {
            $api = ($component['role'] ?? '') === 'database'
                ? $this->coolify->restartDatabase((string) $component['uuid'])
                : $this->coolify->restartApplication((string) $component['uuid']);

            if ($api['success'] ?? false) {
                return [
                    'success' => true,
                    'message' => 'تم طلب إعادة تشغيل «'.$componentName.'» عبر Coolify',
                    'output' => is_string($api['message'] ?? null) ? $api['message'] : null,
                ];
            }
        }

        $ssh = $this->cli->composeServiceLifecycle($site, $componentName, 'restart');
        if ($ssh['success'] ?? false) {
            return [
                'success' => true,
                'message' => 'تم إعادة تشغيل «'.$componentName.'» (docker compose)',
                'output' => $ssh['output'] ?? '',
            ];
        }

        return [
            'success' => false,
            'message' => $ssh['message'] ?? ('فشل إعادة تشغيل «'.$componentName.'»'),
            'output' => $ssh['output'] ?? '',
        ];
    }

    /**
     * @return array{success: bool, message: string, output?: string}
     */
    public function redeploy(CoolifyWordpressSite $site, string $componentName): array
    {
        $componentName = $this->sanitizeComposeServiceName($componentName);
        if ($componentName === '') {
            return ['success' => false, 'message' => 'اسم الحاوية غير صالح'];
        }

        if (! filled($site->service_uuid)) {
            return ['success' => false, 'message' => 'لا توجد خدمة Coolify لهذا الموقع'];
        }

        $serviceResponse = $this->coolify->getService($site->service_uuid);
        if (! ($serviceResponse['success'] ?? false)) {
            return ['success' => false, 'message' => $serviceResponse['message'] ?? 'تعذّر جلب الخدمة من Coolify'];
        }

        $service = is_array($serviceResponse['data'] ?? null) ? $serviceResponse['data'] : [];
        $component = $this->findComponent($service, $componentName);

        if ($component !== null && ($component['uuid'] ?? '') !== '' && ($component['role'] ?? '') === 'database') {
            $api = $this->coolify->redeployDatabase((string) $component['uuid']);
            if ($api['success'] ?? false) {
                return [
                    'success' => true,
                    'message' => $api['message'] ?? 'تم طلب إعادة نشر «'.$componentName.'» عبر Coolify',
                ];
            }
        }

        $ssh = $this->cli->composeServiceLifecycle($site, $componentName, 'redeploy');
        if ($ssh['success'] ?? false) {
            return [
                'success' => true,
                'message' => 'تم إعادة نشر «'.$componentName.'» (docker compose)',
                'output' => $ssh['output'] ?? '',
            ];
        }

        return [
            'success' => false,
            'message' => $ssh['message'] ?? ('فشل إعادة نشر «'.$componentName.'»'),
            'output' => $ssh['output'] ?? '',
        ];
    }

    protected function sanitizeComposeServiceName(string $name): string
    {
        return strtolower(preg_replace('/[^a-z0-9_-]/', '', $name) ?? '');
    }

    /**
     * @param  array<string, mixed>  $service
     * @return array{uuid: string, role: string, name: string}|null
     */
    protected function findComponent(array $service, string $componentName): ?array
    {
        $needle = strtolower($componentName);

        foreach ($this->coolify->normalizeList($service['applications'] ?? []) as $app) {
            if (! is_array($app)) {
                continue;
            }
            $name = strtolower((string) ($app['name'] ?? ''));
            if ($name === $needle) {
                return [
                    'uuid' => (string) ($app['uuid'] ?? ''),
                    'role' => 'application',
                    'name' => $name,
                ];
            }
        }

        foreach ($this->coolify->normalizeList($service['databases'] ?? []) as $db) {
            if (! is_array($db)) {
                continue;
            }
            $name = strtolower((string) ($db['name'] ?? ''));
            if ($name === $needle) {
                return [
                    'uuid' => (string) ($db['uuid'] ?? ''),
                    'role' => 'database',
                    'name' => $name,
                ];
            }
        }

        return null;
    }
}
