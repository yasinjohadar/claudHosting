<?php

namespace App\Services\Backup\StorageDrivers;

use App\Services\Backup\StorageDrivers\S3StorageDriver;

/**
 * Backblaze B2 Storage Driver
 * يستخدم S3-compatible API
 */
class BackblazeStorageDriver extends S3StorageDriver
{
    public function __construct(array $config)
    {
        // Backblaze B2 endpoint format
        $region = $config['region'] ?? 'us-west-000';
        $config['endpoint'] = "https://s3.{$region}.backblazeb2.com";
        $config['use_path_style'] = true;
        
        parent::__construct($config);
    }
}

