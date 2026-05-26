<?php

namespace App\Services\Coolify;

use App\Services\CoolifyApiService;

class CoolifyDatabaseRedeployService
{
    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifySshExecutor $ssh
    ) {}

    /**
     * إعادة نشر/تركيب الحاويات (pull + up) دون حذف المورد من Coolify.
     *
     * @return array{success: bool, message: string, output?: string}
     */
    public function redeploy(string $databaseUuid): array
    {
        $response = $this->coolify->getDatabase($databaseUuid);
        if (! ($response['success'] ?? false)) {
            return ['success' => false, 'message' => $response['message'] ?? 'قاعدة البيانات غير موجودة'];
        }

        $database = is_array($response['data'] ?? null) ? $response['data'] : [];
        $apiRedeploy = $this->coolify->redeployDatabase($databaseUuid);
        if ($apiRedeploy['success'] ?? false) {
            return [
                'success' => true,
                'message' => $apiRedeploy['message'] ?? 'تم طلب إعادة النشر عبر Coolify',
            ];
        }

        $serverUuid = $this->coolify->extractResourceServerUuid($database);
        $endpoint = $this->coolify->resolveServerSshEndpoint($serverUuid);
        if (! ($endpoint['success'] ?? false)) {
            return [
                'success' => false,
                'message' => $endpoint['message'] ?? 'تعذّر تحديد سيرفر SSH لإعادة النشر',
            ];
        }

        $host = (string) ($endpoint['host'] ?? '');
        $port = (int) ($endpoint['port'] ?? 22);
        $name = (string) ($database['name'] ?? $databaseUuid);
        $dirUuid = escapeshellarg('/data/coolify/databases/'.$databaseUuid);
        $dirName = escapeshellarg('/data/coolify/databases/'.$name);

        $cmd = 'if [ -d '.$dirUuid.' ] && { [ -f '.$dirUuid.'/docker-compose.yml ] || [ -f '.$dirUuid.'/docker-compose.yaml ]; }; then '
            .'cd '.$dirUuid.' && docker compose pull && docker compose up -d && exit 0; fi; '
            .'if [ -d '.$dirName.' ] && { [ -f '.$dirName.'/docker-compose.yml ] || [ -f '.$dirName.'/docker-compose.yaml ]; }; then '
            .'cd '.$dirName.' && docker compose pull && docker compose up -d && exit 0; fi; '
            ."echo 'مسار قاعدة البيانات غير موجود على السيرفر'; exit 1";

        $result = $this->ssh->run($host, $cmd, 900, $port);
        if ($result['success'] ?? false) {
            $this->coolify->restartDatabase($databaseUuid);

            return [
                'success' => true,
                'message' => 'تم إعادة نشر قاعدة البيانات (compose) وطُلب إعادة التشغيل',
                'output' => $result['output'] ?? '',
            ];
        }

        return [
            'success' => false,
            'message' => 'فشل إعادة النشر: '.trim($result['output'] ?? ($apiRedeploy['message'] ?? 'خطأ SSH')),
        ];
    }

    /**
     * حذف ثم إنشاء قاعدة بيانات جديدة بنفس الإعدادات (مدمّر للبيانات على الحجم الافتراضي).
     *
     * @return array{success: bool, message: string, uuid?: string}
     */
    public function reinstall(array $database): array
    {
        $uuid = trim((string) ($database['uuid'] ?? $database['id'] ?? ''));
        if ($uuid === '') {
            return ['success' => false, 'message' => 'معرّف قاعدة البيانات غير معروف'];
        }

        $type = $this->resolveDatabaseTypeKey($database);
        if ($type === '') {
            return ['success' => false, 'message' => 'تعذّر تحديد نوع قاعدة البيانات لإعادة التثبيت'];
        }

        $payload = [
            'project_uuid' => (string) ($database['project_uuid'] ?? $database['environment']['project_uuid'] ?? ''),
            'server_uuid' => (string) ($this->coolify->extractResourceServerUuid($database)),
            'environment_name' => (string) ($database['environment_name'] ?? $database['environment']['name'] ?? 'production'),
            'name' => (string) ($database['name'] ?? 'database'),
            'description' => (string) ($database['description'] ?? ''),
            'instant_deploy' => true,
        ];

        if ($payload['project_uuid'] === '' || $payload['server_uuid'] === '') {
            return ['success' => false, 'message' => 'المشروع أو السيرفر غير مرتبطين بهذا المورد'];
        }

        $delete = $this->coolify->deleteDatabase($uuid);
        if (! ($delete['success'] ?? false)) {
            return ['success' => false, 'message' => $delete['message'] ?? 'فشل حذف قاعدة البيانات القديمة'];
        }

        $create = $this->coolify->createDatabase($type, $payload);
        if (! ($create['success'] ?? false)) {
            return ['success' => false, 'message' => $create['message'] ?? 'فشل إنشاء قاعدة البيانات بعد الحذف'];
        }

        $created = $create['data'] ?? null;
        $newUuid = is_array($created) ? trim((string) ($created['uuid'] ?? $created['id'] ?? '')) : '';

        $this->coolify->clearDashboardCache();

        return [
            'success' => true,
            'message' => 'تمت إعادة التثبيت بنجاح',
            'uuid' => $newUuid !== '' ? $newUuid : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $database
     */
    public function resolveDatabaseTypeKey(array $database): string
    {
        foreach (['database_type', 'type', 'engine', 'resource_type', 'internal_db_type'] as $key) {
            $value = strtolower(trim((string) ($database[$key] ?? '')));
            if ($value !== '' && $value !== 'database' && array_key_exists($value, CoolifyApiService::databaseTypes())) {
                return $value;
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $database
     */
    public function catalogInstallUrl(array $database): ?string
    {
        $type = $this->resolveDatabaseTypeKey($database);
        if ($type === '') {
            return null;
        }

        $slug = null;
        foreach (config('coolify_catalog.items', []) as $item) {
            if (($item['category'] ?? '') === 'database' && strtolower((string) ($item['coolify_key'] ?? '')) === $type) {
                $slug = (string) ($item['slug'] ?? '');
                break;
            }
        }

        if ($slug === null || $slug === '') {
            $slug = 'db-'.$type;
        }

        $params = array_filter([
            'step' => 2,
            'project_uuid' => $database['project_uuid'] ?? null,
            'server_uuid' => $this->coolify->extractResourceServerUuid($database) ?: null,
            'environment_name' => $database['environment_name'] ?? ($database['environment']['name'] ?? 'production'),
        ]);

        return route('admin.coolify.catalog.install', array_merge(['slug' => $slug], $params));
    }
}
