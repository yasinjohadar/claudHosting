<?php

namespace App\Services\Backup\StorageDrivers;

use App\Contracts\BackupStorageInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class S3StorageDriver implements BackupStorageInterface
{
    protected array $config;
    protected string $diskName;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->diskName = 's3_custom_' . md5(json_encode($config));
        
        // إعداد disk ديناميكي
        Config::set("filesystems.disks.{$this->diskName}", [
            'driver' => 's3',
            'key' => $config['access_key_id'] ?? '',
            'secret' => $config['secret_access_key'] ?? '',
            'region' => $config['region'] ?? 'us-east-1',
            'bucket' => $config['bucket'] ?? '',
            'url' => $config['url'] ?? null,
            'endpoint' => $config['endpoint'] ?? null,
            'use_path_style_endpoint' => $config['use_path_style'] ?? false,
            'throw' => false,
        ]);
    }

    public function store(string $path, string $content): bool
    {
        try {
            return Storage::disk($this->diskName)->put($path, $content) !== false;
        } catch (\Exception $e) {
            Log::error('S3 storage store failed: ' . $e->getMessage());
            return false;
        }
    }

    public function retrieve(string $path): string
    {
        try {
            return Storage::disk($this->diskName)->get($path);
        } catch (\Exception $e) {
            Log::error('S3 storage retrieve failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(string $path): bool
    {
        try {
            return Storage::disk($this->diskName)->delete($path);
        } catch (\Exception $e) {
            Log::error('S3 storage delete failed: ' . $e->getMessage());
            return false;
        }
    }

    public function exists(string $path): bool
    {
        try {
            return Storage::disk($this->diskName)->exists($path);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function list(string $prefix = ''): array
    {
        try {
            return Storage::disk($this->diskName)->files($prefix);
        } catch (\Exception $e) {
            Log::error('S3 storage list failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getSize(string $path): int
    {
        try {
            return Storage::disk($this->diskName)->size($path);
        } catch (\Exception $e) {
            Log::error('S3 storage getSize failed: ' . $e->getMessage());
            return 0;
        }
    }

    public function testConnection(): bool
    {
        try {
            $testFile = 'test_' . time() . '.txt';
            $result = Storage::disk($this->diskName)->put($testFile, 'test');
            if ($result) {
                Storage::disk($this->diskName)->delete($testFile);
            }
            return $result !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getAvailableSpace(): ?int
    {
        // S3 لا يوفر API مباشر للحصول على المساحة المتاحة
        // يمكن استخدام CloudWatch أو حساب يدوي
        return null;
    }

    public function getMetadata(string $path): array
    {
        try {
            return [
                'size' => Storage::disk($this->diskName)->size($path),
                'last_modified' => Storage::disk($this->diskName)->lastModified($path),
                'url' => Storage::disk($this->diskName)->url($path),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
}

