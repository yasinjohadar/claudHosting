<?php

namespace App\Services\Backup;

use App\Contracts\BackupStorageInterface;
use App\Models\Backup;
use App\Models\BackupStorageConfig;
use App\Models\AppStorageConfig;
use App\Services\Backup\StorageFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class StorageManager
{
    protected StorageAnalyticsService $analyticsService;

    public function __construct(StorageAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * تخزين مع Auto-failover
     * Failover يتم فقط عند الفشل الفعلي للتخزين الأساسي
     */
    public function storeWithFailover(Backup $backup, string $filePath): bool
    {
        $fileContent = file_get_contents($filePath);
        $fileSize = filesize($filePath);

        // محاولة استخدام storageConfig المحدد للنسخة أولاً
        if ($backup->storageConfig && $backup->storageConfig->is_active) {
            try {
                $config = $backup->storageConfig;
                Log::info("Attempting to store backup to: {$config->name} (driver: {$config->driver})");
                
                $driver = StorageFactory::create($config);
                
                // اختبار الاتصال أولاً
                Log::info("Testing connection to: {$config->name}");
                if ($driver->testConnection()) {
                    Log::info("Connection test successful for: {$config->name}");
                    $storagePath = 'backups/' . $backup->id . '/' . basename($filePath);
                    
                    Log::info("Storing backup file to: {$storagePath}");
                    if ($driver->store($storagePath, $fileContent)) {
                        // تتبع الإحصائيات
                        $this->analyticsService->trackStorageUsage($config, $fileSize);
                        $this->analyticsService->trackBandwidth($config, 'upload', $fileSize);
                        
                        // تحديث backup record
                        $backup->update([
                            'storage_driver' => $config->driver,
                            'storage_config_id' => $config->id,
                            'storage_path' => $storagePath,
                        ]);

                        Log::info("Backup stored successfully to: {$config->name}");
                        // عند النجاح، نعود مباشرة دون محاولة failover
                        return true;
                    } else {
                        Log::warning("Failed to store backup file to: {$config->name} - store() returned false");
                        // فشل التخزين، ننتقل إلى failover
                    }
                } else {
                    Log::warning("Connection test failed for: {$config->name}");
                    // فشل اختبار الاتصال، ننتقل إلى failover
                }
            } catch (\Exception $e) {
                Log::error("Primary storage failed: {$backup->storageConfig->name} - {$e->getMessage()}", [
                    'trace' => $e->getTraceAsString(),
                ]);
                // حدث exception، ننتقل إلى failover
            }
        } else {
            Log::warning("Backup storageConfig is missing or inactive", [
                'backup_id' => $backup->id,
                'has_storage_config' => $backup->storageConfig ? 'yes' : 'no',
                'is_active' => $backup->storageConfig?->is_active ?? false,
            ]);
            // لا يوجد storageConfig، ننتقل إلى failover
        }

        // Failover: البحث في AppStorageConfig النشطة الأخرى
        // يتم فقط عند فشل التخزين الأساسي
        Log::info("Attempting failover to other storage locations...");
        $configs = AppStorageConfig::where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        foreach ($configs as $config) {
            // تخطي الـ config الذي فشل للتو
            if ($backup->storageConfig && $config->id === $backup->storageConfig->id) {
                continue;
            }

            try {
                $driver = StorageFactory::create($config);
                
                if ($driver->testConnection()) {
                    $storagePath = 'backups/' . $backup->id . '/' . basename($filePath);
                    
                    if ($driver->store($storagePath, $fileContent)) {
                        // تتبع الإحصائيات
                        $this->analyticsService->trackStorageUsage($config, $fileSize);
                        $this->analyticsService->trackBandwidth($config, 'upload', $fileSize);
                        
                        // تحديث backup record
                        $backup->update([
                            'storage_driver' => $config->driver,
                            'storage_config_id' => $config->id,
                            'storage_path' => $storagePath,
                        ]);

                        Log::info("Backup stored successfully to (failover): {$config->name}");
                        return true;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Failover storage failed: {$config->name} - {$e->getMessage()}");
                continue;
            }
        }

        throw new \Exception('All storage options failed');
    }

    /**
     * تخزين في أماكن متعددة (Redundancy)
     * يتم فقط في الأماكن التي لديها redundancy = true
     * يتخطى المكان الأساسي الذي تم التخزين فيه بالفعل
     */
    public function storeToMultipleStorages(Backup $backup, string $filePath): array
    {
        // التحقق من وجود أماكن تخزين مع تفعيل Redundancy
        $configs = AppStorageConfig::where('is_active', true)
            ->where('redundancy', true)
            ->orderBy('priority', 'desc')
            ->get();

        if ($configs->isEmpty()) {
            Log::info("No redundancy storage configs found, skipping redundancy storage");
            return [
                'successful' => [],
                'failed' => [],
            ];
        }

        // تخطي المكان الأساسي الذي تم التخزين فيه بالفعل
        $configs = $configs->filter(function ($config) use ($backup) {
            return !$backup->storageConfig || $config->id !== $backup->storageConfig->id;
        });

        if ($configs->isEmpty()) {
            Log::info("No additional redundancy storage configs (excluding primary), skipping redundancy storage");
            return [
                'successful' => [],
                'failed' => [],
            ];
        }

        Log::info("Starting redundancy storage to " . $configs->count() . " additional location(s)");

        $fileContent = file_get_contents($filePath);
        $fileSize = filesize($filePath);
        $successfulStorages = [];
        $failedStorages = [];

        foreach ($configs as $config) {
            try {
                $driver = StorageFactory::create($config);
                
                if ($driver->testConnection()) {
                    $storagePath = 'backups/' . $backup->id . '/' . basename($filePath);
                    
                    if ($driver->store($storagePath, $fileContent)) {
                        $this->analyticsService->trackStorageUsage($config, $fileSize);
                        $this->analyticsService->trackBandwidth($config, 'upload', $fileSize);
                        
                        $successfulStorages[] = [
                            'storage_config_id' => $config->id,
                            'storage_config_name' => $config->name,
                            'driver' => $config->driver,
                            'storage_path' => $storagePath,
                        ];
                        
                        Log::info("Redundancy storage successful: {$config->name}");
                    } else {
                        $failedStorages[] = $config->name;
                        Log::warning("Redundancy storage failed: {$config->name} - store() returned false");
                    }
                } else {
                    $failedStorages[] = $config->name;
                    Log::warning("Redundancy storage failed: {$config->name} - connection test failed");
                }
            } catch (\Exception $e) {
                Log::error("Redundancy storage failed: {$config->name} - {$e->getMessage()}");
                $failedStorages[] = $config->name;
            }
        }

        Log::info("Redundancy storage completed. Successful: " . count($successfulStorages) . ", Failed: " . count($failedStorages));

        return [
            'successful' => $successfulStorages,
            'failed' => $failedStorages,
        ];
    }

    /**
     * استرجاع من التخزين
     */
    public function retrieve(Backup $backup): string
    {
        // استخدام storageConfig المحدد للنسخة
        $config = $backup->storageConfig;
        
        if (!$config || !$config->is_active) {
            throw new \Exception("Storage config not found or inactive for backup: {$backup->id}");
        }

        $driver = StorageFactory::create($config);
        $content = $driver->retrieve($backup->storage_path);
        
        // تتبع النطاق الترددي
        $this->analyticsService->trackBandwidth($config, 'download', strlen($content));

        return $content;
    }

    /**
     * حذف من التخزين
     */
    public function delete(Backup $backup): bool
    {
        // استخدام storageConfig المحدد للنسخة
        $config = $backup->storageConfig;
        
        if (!$config || !$config->is_active) {
            return false;
        }

        $driver = StorageFactory::create($config);
        return $driver->delete($backup->storage_path);
    }

    /**
     * Health Check لجميع أماكن التخزين
     */
    public function healthCheck(): Collection
    {
        $configs = AppStorageConfig::where('is_active', true)->get();
        
        return $configs->map(function ($config) {
            try {
                $driver = StorageFactory::create($config);
                $isHealthy = $driver->testConnection();
                
                return [
                    'config' => $config,
                    'healthy' => $isHealthy,
                    'available_space' => $driver->getAvailableSpace(),
                ];
            } catch (\Exception $e) {
                return [
                    'config' => $config,
                    'healthy' => false,
                    'error' => $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Load Balancing - اختيار أفضل مكان تخزين
     */
    public function selectBestStorage(): ?AppStorageConfig
    {
        $configs = AppStorageConfig::where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        foreach ($configs as $config) {
            try {
                $driver = StorageFactory::create($config);
                if ($driver->testConnection()) {
                    return $config;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }
}

