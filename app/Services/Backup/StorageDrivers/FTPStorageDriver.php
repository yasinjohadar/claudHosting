<?php

namespace App\Services\Backup\StorageDrivers;

use App\Contracts\BackupStorageInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class FTPStorageDriver implements BackupStorageInterface
{
    protected array $config;
    protected string $diskName;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->diskName = 'ftp_custom_' . md5(json_encode($config));
        
        $protocol = $config['protocol'] ?? 'ftp';
        
        // إعداد disk ديناميكي
        if ($protocol === 'sftp') {
            Config::set("filesystems.disks.{$this->diskName}", [
                'driver' => 'sftp',
                'host' => $config['host'] ?? '',
                'username' => $config['username'] ?? '',
                'password' => $config['password'] ?? '',
                'port' => $config['port'] ?? 22,
                'root' => $config['root'] ?? '/',
                'timeout' => 30,
            ]);
        } else {
            Config::set("filesystems.disks.{$this->diskName}", [
                'driver' => 'ftp',
                'host' => $config['host'] ?? '',
                'username' => $config['username'] ?? '',
                'password' => $config['password'] ?? '',
                'port' => $config['port'] ?? 21,
                'root' => $config['root'] ?? '/',
                'passive' => $config['passive'] ?? true,
                'ssl' => $config['use_tls'] ?? false,
                'timeout' => 30,
            ]);
        }
    }

    public function store(string $path, string $content): bool
    {
        try {
            return \Storage::disk($this->diskName)->put($path, $content) !== false;
        } catch (\Exception $e) {
            Log::error('FTP storage store failed: ' . $e->getMessage());
            return false;
        }
    }

    public function retrieve(string $path): string
    {
        try {
            return \Storage::disk($this->diskName)->get($path);
        } catch (\Exception $e) {
            Log::error('FTP storage retrieve failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(string $path): bool
    {
        try {
            return \Storage::disk($this->diskName)->delete($path);
        } catch (\Exception $e) {
            Log::error('FTP storage delete failed: ' . $e->getMessage());
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
            Log::error('FTP storage list failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getSize(string $path): int
    {
        try {
            return \Storage::disk($this->diskName)->size($path);
        } catch (\Exception $e) {
            Log::error('FTP storage getSize failed: ' . $e->getMessage());
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
        // FTP لا يوفر API مباشر للمساحة
        return null;
    }

    public function getMetadata(string $path): array
    {
        try {
            return [
                'size' => \Storage::disk($this->diskName)->size($path),
                'last_modified' => \Storage::disk($this->diskName)->lastModified($path),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
}

