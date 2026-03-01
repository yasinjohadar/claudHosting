<?php

namespace App\Services\Backup\StorageDrivers;

use App\Contracts\BackupStorageInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class AzureStorageDriver implements BackupStorageInterface
{
    protected array $config;
    protected string $diskName;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->diskName = 'azure_custom_' . md5(json_encode($config));
        
        // إعداد disk ديناميكي
        Config::set("filesystems.disks.{$this->diskName}", [
            'driver' => 'azure',
            'accountName' => $config['account_name'] ?? '',
            'accountKey' => $config['account_key'] ?? '',
            'container' => $config['container'] ?? '',
            'endpoint' => $config['endpoint'] ?? null,
        ]);
    }

    public function store(string $path, string $content): bool
    {
        try {
            return \Storage::disk($this->diskName)->put($path, $content) !== false;
        } catch (\Exception $e) {
            Log::error('Azure storage store failed: ' . $e->getMessage());
            return false;
        }
    }

    public function retrieve(string $path): string
    {
        try {
            return \Storage::disk($this->diskName)->get($path);
        } catch (\Exception $e) {
            Log::error('Azure storage retrieve failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(string $path): bool
    {
        try {
            return \Storage::disk($this->diskName)->delete($path);
        } catch (\Exception $e) {
            Log::error('Azure storage delete failed: ' . $e->getMessage());
            return false;
        }
    }

    public function exists(string $path): bool
    {
        try {
            return \Storage::disk($this->diskName)->exists($path);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function list(string $prefix = ''): array
    {
        try {
            return \Storage::disk($this->diskName)->files($prefix);
        } catch (\Exception $e) {
            Log::error('Azure storage list failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getSize(string $path): int
    {
        try {
            return \Storage::disk($this->diskName)->size($path);
        } catch (\Exception $e) {
            Log::error('Azure storage getSize failed: ' . $e->getMessage());
            return 0;
        }
    }

    public function testConnection(): bool
    {
        try {
            $testFile = 'test_' . time() . '.txt';
            $result = \Storage::disk($this->diskName)->put($testFile, 'test');
            if ($result) {
                \Storage::disk($this->diskName)->delete($testFile);
            }
            return $result !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getAvailableSpace(): ?int
    {
        // Azure Blob Storage لا يوفر API مباشر
        return null;
    }

    public function getMetadata(string $path): array
    {
        try {
            return [
                'size' => \Storage::disk($this->diskName)->size($path),
                'last_modified' => \Storage::disk($this->diskName)->lastModified($path),
                'url' => \Storage::disk($this->diskName)->url($path),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
}

