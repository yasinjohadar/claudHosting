<?php

namespace App\Services\Backup\StorageDrivers;

use App\Contracts\BackupStorageInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class LocalStorageDriver implements BackupStorageInterface
{
    protected string $root;

    public function __construct(array $config)
    {
        $this->root = $config['path'] ?? 'backups';
    }

    public function store(string $path, string $content): bool
    {
        try {
            return Storage::disk('local')->put($this->root . '/' . $path, $content);
        } catch (\Exception $e) {
            Log::error('Local storage store failed: ' . $e->getMessage());
            return false;
        }
    }

    public function retrieve(string $path): string
    {
        try {
            return Storage::disk('local')->get($this->root . '/' . $path);
        } catch (\Exception $e) {
            Log::error('Local storage retrieve failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(string $path): bool
    {
        try {
            return Storage::disk('local')->delete($this->root . '/' . $path);
        } catch (\Exception $e) {
            Log::error('Local storage delete failed: ' . $e->getMessage());
            return false;
        }
    }

    public function exists(string $path): bool
    {
        return Storage::disk('local')->exists($this->root . '/' . $path);
    }

    public function list(string $prefix = ''): array
    {
        try {
            $fullPrefix = $this->root . '/' . $prefix;
            return Storage::disk('local')->files($fullPrefix);
        } catch (\Exception $e) {
            Log::error('Local storage list failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getSize(string $path): int
    {
        try {
            return Storage::disk('local')->size($this->root . '/' . $path);
        } catch (\Exception $e) {
            Log::error('Local storage getSize failed: ' . $e->getMessage());
            return 0;
        }
    }

    public function testConnection(): bool
    {
        try {
            $testFile = $this->root . '/test_' . time() . '.txt';
            $result = Storage::disk('local')->put($testFile, 'test');
            if ($result) {
                Storage::disk('local')->delete($testFile);
            }
            return $result !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getAvailableSpace(): ?int
    {
        try {
            $path = Storage::disk('local')->path($this->root);
            return disk_free_space($path);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getMetadata(string $path): array
    {
        try {
            $fullPath = $this->root . '/' . $path;
            return [
                'size' => Storage::disk('local')->size($fullPath),
                'last_modified' => Storage::disk('local')->lastModified($fullPath),
                'mime_type' => Storage::disk('local')->mimeType($fullPath),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
}

