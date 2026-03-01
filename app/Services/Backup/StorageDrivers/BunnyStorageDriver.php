<?php

namespace App\Services\Backup\StorageDrivers;

use App\Contracts\BackupStorageInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class BunnyStorageDriver implements BackupStorageInterface
{
    protected array $config;
    protected string $diskName;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->diskName = 'bunny_custom_' . md5(json_encode($config));
        
        // إعداد disk ديناميكي
        // يستخدم package: platformcommunity/flysystem-bunnycdn
        Config::set("filesystems.disks.{$this->diskName}", [
            'driver' => 'bunnycdn',
            'storage_zone' => $config['storage_zone'] ?? '',
            'api_key' => $config['api_key'] ?? '',
            'region' => $config['region'] ?? 'de',
            'pull_zone' => $config['pull_zone'] ?? null, // للوصول العام عبر CDN
            'throw' => true, // إجبار رمي exceptions لإظهار رسالة الخطأ الفعلية
        ]);
    }

    public function store(string $path, string $content): bool
    {
        try {
            $storage = Storage::disk($this->diskName);
            return $storage->put($path, $content) !== false;
        } catch (\Exception $e) {
            Log::error('Bunny Storage store failed: ' . $e->getMessage());
            return false;
        }
    }

    public function retrieve(string $path): string
    {
        try {
            return Storage::disk($this->diskName)->get($path);
        } catch (\Exception $e) {
            Log::error('Bunny Storage retrieve failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(string $path): bool
    {
        try {
            return Storage::disk($this->diskName)->delete($path);
        } catch (\Exception $e) {
            Log::error('Bunny Storage delete failed: ' . $e->getMessage());
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
            Log::error('Bunny Storage list failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getSize(string $path): int
    {
        try {
            return Storage::disk($this->diskName)->size($path);
        } catch (\Exception $e) {
            Log::error('Bunny Storage getSize failed: ' . $e->getMessage());
            return 0;
        }
    }

    public function testConnection(): bool
    {
        try {
            // التحقق من وجود الإعدادات المطلوبة
            if (empty($this->config['storage_zone']) || empty($this->config['api_key'])) {
                Log::error('Bunny Storage testConnection: Missing required config (storage_zone or api_key)');
                return false;
            }

            $testFile = 'test_' . time() . '.txt';
            $result = Storage::disk($this->diskName)->put($testFile, 'test');
            if ($result) {
                Storage::disk($this->diskName)->delete($testFile);
            }
            return $result !== false;
        } catch (\Exception $e) {
            Log::error('Bunny Storage testConnection failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    public function getAvailableSpace(): ?int
    {
        // Bunny Storage API قد يوفر معلومات المساحة المتاحة
        // يحتاج تنفيذ إضافي
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
