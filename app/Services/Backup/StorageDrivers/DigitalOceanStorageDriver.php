<?php

namespace App\Services\Backup\StorageDrivers;

use App\Services\Backup\StorageDrivers\S3StorageDriver;

/**
 * DigitalOcean Spaces Driver
 * يستخدم S3-compatible API
 */
class DigitalOceanStorageDriver extends S3StorageDriver
{
    public function __construct(array $config)
    {
        // DigitalOcean Spaces endpoint format
        $region = $config['region'] ?? 'nyc3';
        $config['endpoint'] = "https://{$region}.digitaloceanspaces.com";
        $config['use_path_style'] = true;
        
        parent::__construct($config);
    }
}

