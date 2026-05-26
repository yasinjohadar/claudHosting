<?php

namespace App\Services\Coolify;

use App\Services\CoolifyApiService;
use RuntimeException;

class GenericResourceInstallerService
{
    public function __construct(
        protected CoolifyApiService $coolify
    ) {}

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>  $validated
     * @return array{success: bool, response: array<string, mixed>, resource_type: string, success_route: string}
     */
    public function install(array $item, array $validated): array
    {
        $category = (string) ($item['category'] ?? '');
        $mode = (string) ($item['install_mode'] ?? '');
        $coolifyKey = trim((string) ($item['coolify_key'] ?? ''));
        $basePayload = $this->basePayload($validated);
        $extraPayload = $this->extraPayload($validated);

        if ($category === 'database') {
            if ($coolifyKey === '') {
                throw new RuntimeException('قاعدة البيانات بدون نوع محدد (coolify_key).');
            }

            return [
                'success' => true,
                'response' => $this->coolify->createDatabase($coolifyKey, array_merge($basePayload, $extraPayload)),
                'resource_type' => 'database',
                'success_route' => 'admin.coolify.databases.show',
            ];
        }

        if ($category === 'service' || ($category === 'custom' && $mode === 'service')) {
            if ($coolifyKey === '') {
                throw new RuntimeException('الخدمة بدون نوع محدد (coolify_key).');
            }

            return [
                'success' => true,
                'response' => $this->coolify->createService(array_merge($basePayload, ['type' => $coolifyKey], $extraPayload)),
                'resource_type' => 'service',
                'success_route' => 'admin.coolify.services.show',
            ];
        }

        if ($category === 'application' || ($category === 'custom' && $mode === 'application')) {
            $response = $this->createApplicationByType($coolifyKey, array_merge($basePayload, $extraPayload));

            return [
                'success' => true,
                'response' => $response,
                'resource_type' => 'application',
                'success_route' => 'admin.coolify.applications.show',
            ];
        }

        throw new RuntimeException('نوع المورد غير مدعوم للتثبيت المباشر.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function basePayload(array $validated): array
    {
        return [
            'project_uuid' => $validated['project_uuid'],
            'server_uuid' => $validated['server_uuid'],
            'environment_name' => $validated['environment_name'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function extraPayload(array $validated): array
    {
        $raw = trim((string) ($validated['extra_payload'] ?? ''));
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('حقل JSON الإضافي غير صالح.');
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function createApplicationByType(string $type, array $payload): array
    {
        $type = strtolower(trim($type));

        return match ($type) {
            'public' => $this->coolify->createApplicationPublic($payload),
            'private-github-app', 'private_github_app' => $this->coolify->createApplicationPrivateGithub($payload),
            'private-deploy-key', 'private_deploy_key' => $this->coolify->createApplicationPrivateDeployKey($payload),
            'dockerfile' => $this->coolify->createApplicationDockerfile($payload),
            'dockerimage', 'docker-image' => $this->coolify->createApplicationDockerImage($payload),
            'dockercompose', 'docker-compose' => $this->coolify->createApplicationDockerCompose($payload),
            default => throw new RuntimeException('نوع التطبيق غير مدعوم: '.$type),
        };
    }
}
