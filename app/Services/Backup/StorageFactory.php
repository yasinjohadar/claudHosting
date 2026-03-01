<?php

namespace App\Services\Backup;

use App\Contracts\BackupStorageInterface;
use App\Models\BackupStorageConfig;
use App\Models\AppStorageConfig;
use App\Services\Backup\StorageDrivers\LocalStorageDriver;
use App\Services\Backup\StorageDrivers\S3StorageDriver;
use App\Services\Backup\StorageDrivers\AzureStorageDriver;
use App\Services\Backup\StorageDrivers\FTPStorageDriver;
use App\Services\Backup\StorageDrivers\DigitalOceanStorageDriver;
use App\Services\Backup\StorageDrivers\WasabiStorageDriver;
use App\Services\Backup\StorageDrivers\BackblazeStorageDriver;
use App\Services\Backup\StorageDrivers\CloudflareR2StorageDriver;
use App\Services\Backup\StorageDrivers\GoogleDriveStorageDriver;
use App\Services\Backup\StorageDrivers\BunnyStorageDriver;
use Illuminate\Support\Facades\Log;

class StorageFactory
{
    /**
     * إنشاء instance من Storage Driver
     * يدعم كلا من BackupStorageConfig و AppStorageConfig
     */
    public static function create(BackupStorageConfig|AppStorageConfig $config): BackupStorageInterface
    {
        $driverConfig = $config->getDecryptedConfig();
        
        return match($config->driver) {
            'local' => new LocalStorageDriver($driverConfig),
            's3' => new S3StorageDriver($driverConfig),
            'azure' => new AzureStorageDriver($driverConfig),
            'ftp', 'sftp' => new FTPStorageDriver($driverConfig),
            'digitalocean' => new DigitalOceanStorageDriver($driverConfig),
            'wasabi' => new WasabiStorageDriver($driverConfig),
            'backblaze' => new BackblazeStorageDriver($driverConfig),
            'cloudflare_r2' => new CloudflareR2StorageDriver($driverConfig),
            'google_drive' => new GoogleDriveStorageDriver($driverConfig),
            'bunny' => new BunnyStorageDriver($driverConfig),
            default => throw new \Exception("نوع التخزين غير مدعوم: {$config->driver}"),
        };
    }

    /**
     * إنشاء instance من Storage Driver من array config
     */
    public static function createFromArray(string $driver, array $config): BackupStorageInterface
    {
        return match($driver) {
            'local' => new LocalStorageDriver($config),
            's3' => new S3StorageDriver($config),
            'azure' => new AzureStorageDriver($config),
            'ftp', 'sftp' => new FTPStorageDriver($config),
            'digitalocean' => new DigitalOceanStorageDriver($config),
            'wasabi' => new WasabiStorageDriver($config),
            'backblaze' => new BackblazeStorageDriver($config),
            'cloudflare_r2' => new CloudflareR2StorageDriver($config),
            'google_drive' => new GoogleDriveStorageDriver($config),
            'bunny' => new BunnyStorageDriver($config),
            default => throw new \Exception("نوع التخزين غير مدعوم: {$driver}"),
        };
    }
}

