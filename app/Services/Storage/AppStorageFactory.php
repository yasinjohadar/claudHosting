<?php

namespace App\Services\Storage;

use App\Models\AppStorageConfig;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AppStorageFactory
{
    /**
     * إنشاء Laravel Storage disk من AppStorageConfig
     */
    public static function create(AppStorageConfig $config): \Illuminate\Contracts\Filesystem\Filesystem
    {
        // استخدام fresh() لضمان قراءة القيمة المحدثة من قاعدة البيانات
        $freshConfig = $config->fresh();
        
        $driverConfig = $freshConfig->getDecryptedConfig();
        $diskName = 'app_storage_' . $freshConfig->id . '_' . md5(json_encode($driverConfig));
        
        $diskConfig = match($freshConfig->driver) {
            'local' => self::getLocalConfig($driverConfig),
            's3' => self::getS3Config($driverConfig),
            'google_drive' => self::getGoogleDriveConfig($driverConfig),
            'dropbox' => self::getDropboxConfig($driverConfig),
            'azure' => self::getAzureConfig($driverConfig),
            'ftp', 'sftp' => self::getFTPConfig($driverConfig),
            'digitalocean' => self::getDigitalOceanConfig($driverConfig),
            'wasabi' => self::getWasabiConfig($driverConfig),
            'backblaze' => self::getBackblazeConfig($driverConfig),
            'cloudflare_r2' => self::getCloudflareR2Config($driverConfig),
            'bunny' => self::getBunnyConfig($driverConfig),
            default => throw new \Exception("نوع التخزين غير مدعوم: {$freshConfig->driver}"),
        };

        // إضافة CDN URL إذا كان موجوداً
        if ($freshConfig->cdn_url) {
            $diskConfig['url'] = rtrim($freshConfig->cdn_url, '/');
        }

        // تسجيل الـ disk ديناميكياً
        Config::set("filesystems.disks.{$diskName}", $diskConfig);

        return Storage::disk($diskName);
    }

    /**
     * Local Storage Config
     */
    private static function getLocalConfig(array $config): array
    {
        return [
            'driver' => 'local',
            'root' => storage_path('app/' . ($config['path'] ?? 'public')),
            'visibility' => 'public',
            'throw' => false,
        ];
    }

    /**
     * S3 Config
     */
    private static function getS3Config(array $config): array
    {
        return [
            'driver' => 's3',
            'key' => $config['access_key_id'] ?? '',
            'secret' => $config['secret_access_key'] ?? '',
            'region' => $config['region'] ?? 'us-east-1',
            'bucket' => $config['bucket'] ?? '',
            'url' => $config['url'] ?? null,
            'endpoint' => $config['endpoint'] ?? null,
            'use_path_style_endpoint' => $config['use_path_style'] ?? false,
            'throw' => false,
        ];
    }

    /**
     * Google Drive Config
     * يستخدم package: yaza/laravel-google-drive-storage
     */
    private static function getGoogleDriveConfig(array $config): array
    {
        return [
            'driver' => 'google',
            'clientId' => $config['client_id'] ?? '',
            'clientSecret' => $config['client_secret'] ?? '',
            'refreshToken' => $config['refresh_token'] ?? '',
            'folder' => $config['folder_id'] ?? null, // package يستخدم 'folder' وليس 'folderId'
            'throw' => true, // إجبار رمي exceptions لإظهار رسالة الخطأ الفعلية
        ];
    }

    /**
     * Dropbox Config
     */
    private static function getDropboxConfig(array $config): array
    {
        return [
            'driver' => 'dropbox',
            'authorizationToken' => $config['access_token'] ?? '',
        ];
    }

    /**
     * Azure Config
     */
    private static function getAzureConfig(array $config): array
    {
        return [
            'driver' => 'azure',
            'accountName' => $config['account_name'] ?? '',
            'accountKey' => $config['account_key'] ?? '',
            'container' => $config['container'] ?? '',
            'endpoint' => $config['endpoint'] ?? null,
        ];
    }

    /**
     * FTP/SFTP Config
     */
    private static function getFTPConfig(array $config): array
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
                'timeout' => 30,
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
            'timeout' => 30,
        ];
    }

    /**
     * DigitalOcean Spaces Config
     */
    private static function getDigitalOceanConfig(array $config): array
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
            'throw' => false,
        ];
    }

    /**
     * Wasabi Config
     */
    private static function getWasabiConfig(array $config): array
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
            'throw' => false,
        ];
    }

    /**
     * Backblaze B2 Config
     */
    private static function getBackblazeConfig(array $config): array
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
            'throw' => false,
        ];
    }

    /**
     * Cloudflare R2 Config
     */
    private static function getCloudflareR2Config(array $config): array
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
            'throw' => false,
        ];
    }

    /**
     * Bunny Storage Config
     * يستخدم package: platformcommunity/flysystem-bunnycdn
     */
    private static function getBunnyConfig(array $config): array
    {
        return [
            'driver' => 'bunnycdn',
            'storage_zone' => $config['storage_zone'] ?? '',
            'api_key' => $config['api_key'] ?? '',
            'region' => $config['region'] ?? 'de',
            'pull_zone' => $config['pull_zone'] ?? '', // للوصول العام عبر CDN
            'throw' => true, // إجبار رمي exceptions لإظهار رسالة الخطأ الفعلية
        ];
    }
}

