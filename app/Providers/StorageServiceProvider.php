<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use App\Models\StorageDiskMapping;
use App\Services\Storage\AppStorageFactory;
use Illuminate\Support\Facades\Log;

class StorageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // تسجيل Bunny Storage Driver
        $this->registerBunnyDriver();
        
        // تسجيل Google Drive Driver
        $this->registerGoogleDriveDriver();

        // تسجيل الـ disks ديناميكياً من قاعدة البيانات
        try {
            $this->registerDynamicDisks();
        } catch (\Exception $e) {
            // في حالة عدم وجود الجداول بعد (أثناء migration)
            Log::debug('StorageServiceProvider: Could not register dynamic disks - ' . $e->getMessage());
        }
    }

    /**
     * تسجيل Bunny Storage Driver
     */
    private function registerBunnyDriver(): void
    {
        try {
            Storage::extend('bunnycdn', function ($app, $config) {
                $client = new \PlatformCommunity\Flysystem\BunnyCDN\BunnyCDNClient(
                    $config['storage_zone'] ?? '',
                    $config['api_key'] ?? '',
                    $config['region'] ?? 'de'
                );

                $adapter = new \PlatformCommunity\Flysystem\BunnyCDN\BunnyCDNAdapter(
                    $client,
                    $config['pull_zone'] ?? ''
                );

                $filesystem = new \League\Flysystem\Filesystem($adapter);

                return new \Illuminate\Filesystem\FilesystemAdapter(
                    $filesystem,
                    $adapter,
                    $config
                );
            });
        } catch (\Exception $e) {
            // في حالة عدم تثبيت package بعد
            Log::debug('StorageServiceProvider: Could not register bunnycdn driver - ' . $e->getMessage());
        }
    }

    /**
     * تسجيل Google Drive Driver
     */
    private function registerGoogleDriveDriver(): void
    {
        try {
            // التحقق من وجود package
            if (!class_exists(\Google\Client::class)) {
                Log::debug('StorageServiceProvider: Google Drive package not installed (Google\Client not found)');
                return;
            }

            Storage::extend('google', function ($app, $config) {
                $options = [];

                if (!empty($config['teamDriveId'] ?? null)) {
                    $options['teamDriveId'] = $config['teamDriveId'];
                }

                $client = new \Google\Client;
                $client->setClientId($config['clientId']);
                $client->setClientSecret($config['clientSecret']);
                $client->refreshToken($config['refreshToken']);

                if (isset($config['accessToken'])) {
                    $client->setAccessToken($config['accessToken']);
                }

                $service = new \Google\Service\Drive($client);
                $adapter = new \Masbug\Flysystem\GoogleDriveAdapter($service, $config['folder'] ?? '/', $options);
                $driver = new \League\Flysystem\Filesystem($adapter);

                return new \Illuminate\Filesystem\FilesystemAdapter($driver, $adapter);
            });
        } catch (\Exception $e) {
            // في حالة عدم تثبيت package بعد
            Log::debug('StorageServiceProvider: Could not register google driver - ' . $e->getMessage());
        }
    }

    /**
     * تسجيل الـ disks ديناميكياً
     */
    private function registerDynamicDisks(): void
    {
        $mappings = StorageDiskMapping::with('primaryStorage')
            ->where('is_active', true)
            ->get();

        foreach ($mappings as $mapping) {
            if (!$mapping->primaryStorage || !$mapping->primaryStorage->is_active) {
                continue;
            }

            try {
                $diskName = $mapping->disk_name;
                
                // إنشاء disk configuration
                $diskConfig = $this->getDiskConfig($mapping->primaryStorage);
                
                // تسجيل الـ disk
                Config::set("filesystems.disks.{$diskName}", $diskConfig);
            } catch (\Exception $e) {
                Log::error("Failed to register disk {$mapping->disk_name}: " . $e->getMessage());
            }
        }
    }

    /**
     * الحصول على disk configuration
     */
    private function getDiskConfig($storageConfig): array
    {
        $config = $storageConfig->getDecryptedConfig();
        
        $baseConfig = match($storageConfig->driver) {
            'local' => [
                'driver' => 'local',
                'root' => storage_path('app/' . ($config['path'] ?? 'public')),
                'visibility' => 'public',
            ],
            's3' => [
                'driver' => 's3',
                'key' => $config['access_key_id'] ?? '',
                'secret' => $config['secret_access_key'] ?? '',
                'region' => $config['region'] ?? 'us-east-1',
                'bucket' => $config['bucket'] ?? '',
                'endpoint' => $config['endpoint'] ?? null,
                'use_path_style_endpoint' => $config['use_path_style'] ?? false,
            ],
            'google_drive' => [
                'driver' => 'google',
                'clientId' => $config['client_id'] ?? '',
                'clientSecret' => $config['client_secret'] ?? '',
                'refreshToken' => $config['refresh_token'] ?? '',
                'folder' => $config['folder_id'] ?? null, // package يستخدم 'folder' وليس 'folderId'
                'throw' => true, // إجبار رمي exceptions لإظهار رسالة الخطأ الفعلية
            ],
            'bunny' => [
                'driver' => 'bunnycdn',
                'storage_zone' => $config['storage_zone'] ?? '',
                'api_key' => $config['api_key'] ?? '',
                'region' => $config['region'] ?? 'de',
                'pull_zone' => $config['pull_zone'] ?? '', // للوصول العام عبر CDN
                'throw' => true, // إجبار رمي exceptions لإظهار رسالة الخطأ الفعلية
            ],
            'dropbox' => [
                'driver' => 'dropbox',
                'authorizationToken' => $config['access_token'] ?? '',
            ],
            'azure' => [
                'driver' => 'azure',
                'accountName' => $config['account_name'] ?? '',
                'accountKey' => $config['account_key'] ?? '',
                'container' => $config['container'] ?? '',
                'endpoint' => $config['endpoint'] ?? null,
            ],
            'ftp', 'sftp' => $this->getFTPConfig($config),
            'digitalocean' => $this->getDigitalOceanConfig($config),
            'wasabi' => $this->getWasabiConfig($config),
            'backblaze' => $this->getBackblazeConfig($config),
            'cloudflare_r2' => $this->getCloudflareR2Config($config),
            default => ['driver' => 'local', 'root' => storage_path('app/public')],
        };

        // إضافة CDN URL
        if ($storageConfig->cdn_url) {
            $baseConfig['url'] = rtrim($storageConfig->cdn_url, '/');
        }

        $baseConfig['throw'] = false;
        
        return $baseConfig;
    }

    private function getFTPConfig(array $config): array
    {
        $protocol = $config['protocol'] ?? 'ftp';
        
        if ($protocol === 'sftp') {
            return [
                'driver' => 'sftp',
                'host' => $config['host'] ?? '',
                'username' => $config['username'] ?? '',
                'password' => $config['password'] ?? '',
                'port' => $config['port'] ?? 22,
                'root' => $config['root'] ?? '/',
            ];
        }

        return [
            'driver' => 'ftp',
            'host' => $config['host'] ?? '',
            'username' => $config['username'] ?? '',
            'password' => $config['password'] ?? '',
            'port' => $config['port'] ?? 21,
            'root' => $config['root'] ?? '/',
            'passive' => $config['passive'] ?? true,
            'ssl' => $config['use_tls'] ?? false,
        ];
    }

    private function getDigitalOceanConfig(array $config): array
    {
        $region = $config['region'] ?? 'nyc3';
        return [
            'driver' => 's3',
            'key' => $config['access_key_id'] ?? '',
            'secret' => $config['secret_access_key'] ?? '',
            'region' => $region,
            'bucket' => $config['bucket'] ?? '',
            'endpoint' => "https://{$region}.digitaloceanspaces.com",
            'use_path_style_endpoint' => true,
        ];
    }

    private function getWasabiConfig(array $config): array
    {
        $region = $config['region'] ?? 'us-east-1';
        return [
            'driver' => 's3',
            'key' => $config['access_key_id'] ?? '',
            'secret' => $config['secret_access_key'] ?? '',
            'region' => $region,
            'bucket' => $config['bucket'] ?? '',
            'endpoint' => "https://s3.{$region}.wasabisys.com",
            'use_path_style_endpoint' => true,
        ];
    }

    private function getBackblazeConfig(array $config): array
    {
        $region = $config['region'] ?? 'us-west-000';
        return [
            'driver' => 's3',
            'key' => $config['access_key_id'] ?? '',
            'secret' => $config['secret_access_key'] ?? '',
            'region' => $region,
            'bucket' => $config['bucket'] ?? '',
            'endpoint' => "https://s3.{$region}.backblazeb2.com",
            'use_path_style_endpoint' => true,
        ];
    }

    private function getCloudflareR2Config(array $config): array
    {
        $accountId = $config['account_id'] ?? '';
        return [
            'driver' => 's3',
            'key' => $config['access_key_id'] ?? '',
            'secret' => $config['secret_access_key'] ?? '',
            'region' => 'auto',
            'bucket' => $config['bucket'] ?? '',
            'endpoint' => "https://{$accountId}.r2.cloudflarestorage.com",
            'use_path_style_endpoint' => true,
        ];
    }
}
