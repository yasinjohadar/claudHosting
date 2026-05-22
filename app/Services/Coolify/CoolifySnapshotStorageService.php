<?php

namespace App\Services\Coolify;

use App\Models\AppStorageConfig;
use App\Services\Storage\AppStorageFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CoolifySnapshotStorageService
{
    public function __construct(protected CoolifySettingsService $settings) {}

    public function isConfigured(): bool
    {
        return $this->getSnapshotStorageConfig() !== null;
    }

    public function getSnapshotStorageConfig(): ?AppStorageConfig
    {
        $id = $this->settings->getSnapshotStorageConfigId();
        if ($id <= 0) {
            return null;
        }

        $config = AppStorageConfig::query()
            ->where('id', $id)
            ->where('is_active', true)
            ->first();

        if (! $config) {
            return null;
        }

        $allowed = config('coolify.snapshot_storage_drivers', ['s3']);

        if (! in_array($config->driver, $allowed, true)) {
            return null;
        }

        return $config;
    }

    public function disk(): Filesystem
    {
        $config = $this->getSnapshotStorageConfig();
        if (! $config) {
            throw new \RuntimeException('لم يتم اختيار تخزين S3 للقطات من إعدادات Coolify');
        }

        return AppStorageFactory::create($config);
    }

    public function objectKey(string $snapshotUuid, string $resourceUuid, string $filename): string
    {
        $prefix = trim($this->settings->getS3Prefix(), '/');
        $snapshot = Str::slug($snapshotUuid);
        $resource = Str::slug($resourceUuid);

        return $prefix.'/'.$snapshot.'/'.$resource.'/'.$filename;
    }

    public function formatBackupPath(string $objectKey): string
    {
        $id = $this->settings->getSnapshotStorageConfigId();

        return 's3:'.$id.':'.ltrim($objectKey, '/');
    }

    public function parseBackupPath(string $backupPath): ?string
    {
        if (! str_starts_with($backupPath, 's3:')) {
            return null;
        }

        $parts = explode(':', $backupPath, 3);
        if (count($parts) < 3) {
            return null;
        }

        return $parts[2];
    }

    public function uploadLocalFile(string $objectKey, string $localPath): void
    {
        if (! is_file($localPath)) {
            throw new \RuntimeException('الملف المحلي غير موجود: '.$localPath);
        }

        $stream = fopen($localPath, 'rb');
        if ($stream === false) {
            throw new \RuntimeException('تعذر فتح الملف للرفع');
        }

        try {
            $ok = $this->disk()->put($objectKey, $stream);
            if (! $ok) {
                throw new \RuntimeException('فشل رفع الملف إلى S3: '.$objectKey);
            }
        } finally {
            fclose($stream);
        }
    }

    public function uploadJson(string $objectKey, array $data): void
    {
        $ok = $this->disk()->put($objectKey, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        if (! $ok) {
            throw new \RuntimeException('فشل رفع manifest إلى S3');
        }
    }

    public function downloadToLocalFile(string $objectKey, string $localPath): void
    {
        File::ensureDirectoryExists(dirname($localPath));

        if (! $this->disk()->exists($objectKey)) {
            throw new \RuntimeException('الملف غير موجود على S3: '.$objectKey);
        }

        $contents = $this->disk()->get($objectKey);
        File::put($localPath, $contents);
    }

    public function deleteObject(string $objectKey): void
    {
        if ($this->disk()->exists($objectKey)) {
            $this->disk()->delete($objectKey);
        }
    }

    public function laravelTempDir(): string
    {
        $dir = storage_path('app/'.trim($this->settings->getSshKeyCachePath(), '/').'/tmp');
        File::ensureDirectoryExists($dir);

        return $dir;
    }
}
