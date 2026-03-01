<?php

namespace App\Services\Backup\StorageDrivers;

use App\Services\Backup\StorageDrivers\S3StorageDriver;

/**
 * Cloudflare R2 Storage Driver
 * يستخدم S3-compatible API
 */
class CloudflareR2StorageDriver extends S3StorageDriver
{
    public function __construct(array $config)
    {
        // Cloudflare R2 endpoint format
        $accountId = $config['account_id'] ?? '';
        $config['endpoint'] = "https://{$accountId}.r2.cloudflarestorage.com";
        $config['use_path_style'] = true;
        $config['region'] = 'auto'; // R2 لا يستخدم regions
        
        parent::__construct($config);
    }
}

