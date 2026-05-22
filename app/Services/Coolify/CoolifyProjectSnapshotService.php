<?php

namespace App\Services\Coolify;

use App\Models\CoolifyProjectSnapshot;
use App\Models\CoolifyProjectSnapshotItem;
use App\Services\CoolifyApiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CoolifyProjectSnapshotService
{
    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifyBackupService $backupService,
        protected CoolifySshExecutor $ssh,
        protected CoolifySnapshotStorageService $storage,
        protected CoolifySettingsService $settings
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $plan
     */
    public function createFromPlan(array $plan, array $meta): CoolifyProjectSnapshot
    {
        $options = array_merge([
            'frequency' => 'daily',
            'save_s3' => true,
            's3_storage_uuid' => $this->settings->getCoolifyS3StorageUuid(),
            'storage_config_id' => $this->settings->getSnapshotStorageConfigId(),
            's3_prefix' => $this->settings->getS3Prefix(),
        ], $meta['options'] ?? []);

        $snapshot = CoolifyProjectSnapshot::create([
            'scope' => $meta['scope'] ?? 'single_project',
            'project_uuid' => $meta['project_uuid'] ?? null,
            'project_name' => $meta['project_name'] ?? null,
            'name' => $meta['name'] ?? 'لقطة '.now()->format('Y-m-d H:i'),
            'status' => 'pending',
            'options' => $options,
            'created_by' => Auth::id(),
        ]);

        foreach ($plan as $row) {
            if (! filter_var($row['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }

            $resourceUuid = (string) ($row['resource_uuid'] ?? '');
            if ($resourceUuid === '') {
                continue;
            }

            CoolifyProjectSnapshotItem::create([
                'snapshot_id' => $snapshot->id,
                'resource_type' => (string) ($row['resource_type'] ?? 'resource'),
                'resource_uuid' => $resourceUuid,
                'resource_name' => $row['resource_name'] ?? null,
                'project_uuid' => $row['project_uuid'] ?? null,
                'server_uuid' => $row['server_uuid'] ?? null,
                'server_host' => $row['server_host'] ?? null,
                'strategy' => (string) ($row['strategy'] ?? 'manifest_only'),
                'status' => 'pending',
                'metadata' => [],
            ]);
        }

        return $snapshot->fresh('items');
    }

    public function processItem(CoolifyProjectSnapshotItem $item, CoolifyProjectSnapshot $snapshot): void
    {
        if (! $this->storage->isConfigured()) {
            throw new \RuntimeException('اختر تخزين S3 من إعدادات Coolify (ربط الأقراص → App Storage)');
        }

        $item->update(['status' => 'running', 'started_at' => now()]);
        $options = $snapshot->options ?? [];

        try {
            match ($item->strategy) {
                'coolify_api' => $this->backupDatabaseItem($item, $options),
                'ssh_volume' => $this->backupVolumeItem($item, $snapshot),
                default => $this->backupManifestOnly($item, $snapshot),
            };

            $item->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $item->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }

        $snapshot->refreshStatusFromItems();
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function backupDatabaseItem(CoolifyProjectSnapshotItem $item, array $options): void
    {
        $s3Uuid = trim((string) ($options['s3_storage_uuid'] ?? $this->settings->getCoolifyS3StorageUuid()));
        if ($s3Uuid === '') {
            throw new \RuntimeException('أدخل UUID تخزين S3 في Coolify من إعدادات اللوحة (لنسخ قواعد البيانات عبر Coolify API)');
        }

        $payload = $this->backupService->backupPayloadFromRequest(array_merge([
            'frequency' => $options['frequency'] ?? 'daily',
            'enabled' => true,
            'backup_now' => true,
            'save_s3' => true,
            's3_storage_uuid' => $s3Uuid,
            'database_backup_retention_amount_locally' => 0,
            'database_backup_retention_days_locally' => 0,
        ], $options));

        $response = $this->coolify->createDatabaseBackup($item->resource_uuid, $payload);

        if (! ($response['success'] ?? false)) {
            throw new \RuntimeException($response['message'] ?? 'فشل نسخ قاعدة البيانات');
        }

        $configUuid = is_array($response['data'] ?? null)
            ? (string) ($response['data']['uuid'] ?? '')
            : '';

        $item->update([
            'coolify_backup_config_uuid' => $configUuid ?: null,
            'backup_path' => $configUuid
                ? 'coolify-s3:'.$item->resource_uuid.'/'.$configUuid
                : 'coolify-s3:'.$item->resource_uuid,
            'metadata' => [
                'api_response' => $response['data'] ?? [],
                'save_s3' => true,
                's3_storage_uuid' => $s3Uuid,
            ],
        ]);
    }

    protected function backupVolumeItem(CoolifyProjectSnapshotItem $item, CoolifyProjectSnapshot $snapshot): void
    {
        $host = trim((string) $item->server_host);
        if ($host === '') {
            throw new \RuntimeException('لا يوجد عنوان IP للسيرفر');
        }

        $storagesResponse = $this->coolify->listApplicationStorages($item->resource_uuid);
        $storages = ($storagesResponse['success'] ?? false)
            ? $this->coolify->normalizeList($storagesResponse['data'] ?? [])
            : [];

        $envResponse = $this->coolify->listApplicationEnvs($item->resource_uuid);
        $uploaded = [];
        $tag = $snapshot->uuid.'-'.$item->id;

        foreach ($storages as $storage) {
            $volumeName = (string) ($storage['name'] ?? $storage['volume_name'] ?? '');
            if ($volumeName === '') {
                continue;
            }

            $remotePath = $this->ssh->remoteTempArchivePath($tag, $volumeName);
            $result = $this->ssh->backupVolumeToTemp($host, $volumeName, $tag);
            if (! ($result['success'] ?? false)) {
                $this->ssh->removeRemoteFile($host, $remotePath);
                throw new \RuntimeException('فشل إنشاء أرشيف volume '.$volumeName.': '.($result['output'] ?? ''));
            }

            $localPath = $this->storage->laravelTempDir().'/'.Str::random(16).'.tar.gz';
            try {
                $dl = $this->ssh->downloadFile($host, $remotePath, $localPath);
                $this->ssh->removeRemoteFile($host, $remotePath);

                if (! ($dl['success'] ?? false)) {
                    throw new \RuntimeException('فشل تنزيل الأرشيف من السيرفر: '.($dl['output'] ?? ''));
                }

                $objectKey = $this->storage->objectKey(
                    $snapshot->uuid,
                    $item->resource_uuid,
                    $volumeName.'.tar.gz'
                );
                $this->storage->uploadLocalFile($objectKey, $localPath);

                $uploaded[] = [
                    'volume_name' => $volumeName,
                    's3_key' => $objectKey,
                    'size' => filesize($localPath) ?: 0,
                ];
            } finally {
                if (is_file($localPath)) {
                    @unlink($localPath);
                }
            }
        }

        $manifestKey = $this->storage->objectKey($snapshot->uuid, $item->resource_uuid, 'manifest.json');
        $manifest = [
            'envs' => ($envResponse['success'] ?? false) ? ($envResponse['data'] ?? []) : [],
            'storages' => $storages,
            'volumes' => $uploaded,
        ];
        $this->storage->uploadJson($manifestKey, $manifest);

        $item->update([
            'backup_path' => $this->storage->formatBackupPath($manifestKey),
            'metadata' => [
                'volumes' => $uploaded,
                'manifest_s3_key' => $manifestKey,
                'storage' => 's3_only',
            ],
        ]);
    }

    protected function backupManifestOnly(CoolifyProjectSnapshotItem $item, CoolifyProjectSnapshot $snapshot): void
    {
        $details = $this->coolify->getResourceDetails($item->resource_type, $item->resource_uuid);
        $filename = $item->resource_type === 'database'
            ? 'database-manifest.json'
            : 'resource-manifest.json';

        $manifestKey = $this->storage->objectKey($snapshot->uuid, $item->resource_uuid, $filename);

        $payload = [
            'resource' => $details ?? [],
            'storage' => 's3_only',
        ];

        if ($item->resource_type === 'database' && $this->settings->getCoolifyS3StorageUuid() === '') {
            $payload['db_backup_note'] = 'لم يُضبط UUID S3 في Coolify: تُحفظ بيانات وصفية فقط. لنسخ DB عبر Coolify API أضف UUID من Storages في Coolify، أو أنشئ جدولة نسخ DB على S3 ثم «جلب من Coolify».';
        }

        $this->storage->uploadJson($manifestKey, $payload);

        $item->update([
            'metadata' => ['manifest_s3_key' => $manifestKey, 'storage' => 's3_only'],
            'backup_path' => $this->storage->formatBackupPath($manifestKey),
        ]);
    }
}
