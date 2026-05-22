<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Coolify — مفاتيح الإعدادات (تُخزَّن في system_settings.group = coolify)
    | القيم الفعلية تُضبط من لوحة التحكم → إعدادات Coolify
    |--------------------------------------------------------------------------
    */
    'keys' => [
        'api_url' => 'coolify_api_url',
        'api_token' => 'coolify_api_token',
        'timeout' => 'coolify_api_timeout',
        'ssh_user' => 'coolify_ssh_user',
        'ssh_private_key' => 'coolify_ssh_private_key',
        'ssh_private_key_path' => 'coolify_ssh_private_key_path',
        'ssh_key_cache_path' => 'coolify_ssh_key_cache_path',
        /** عنوان SSH الحقيقي عندما يكون IP السيرفر في Coolify = host.docker.internal أو localhost */
        'ssh_host_fallback' => 'coolify_ssh_host_fallback',
        'ssh_port' => 'coolify_ssh_port',
        'backup_queue' => 'coolify_backup_queue',
        'snapshot_storage_config_id' => 'coolify_snapshot_storage_config_id',
        'coolify_s3_storage_uuid' => 'coolify_s3_storage_uuid',
        's3_prefix' => 'coolify_s3_prefix',
        'wordpress_base_domain' => 'coolify_wordpress_base_domain',
        'wordpress_default_server_uuid' => 'coolify_wordpress_default_server_uuid',
        'wordpress_shared_project_uuid' => 'coolify_wordpress_shared_project_uuid',
        'wordpress_default_environment' => 'coolify_wordpress_default_environment',
        'wordpress_instant_deploy' => 'coolify_wordpress_instant_deploy',
        'wordpress_provision_queue' => 'coolify_wordpress_provision_queue',
        'wordpress_default_destination_uuid' => 'coolify_wordpress_default_destination_uuid',
        'wordpress_service_type' => 'coolify_wordpress_service_type',
        'wordpress_cloudflare_zone_id' => 'coolify_wordpress_cloudflare_zone_id',
        'wordpress_cloudflare_proxied' => 'coolify_wordpress_cloudflare_proxied',
        'wordpress_cloudflare_ssl_mode' => 'coolify_wordpress_cloudflare_ssl_mode',
        'wordpress_security_preset' => 'coolify_wordpress_security_preset',
        'wordpress_cloudflare_enabled' => 'coolify_wordpress_cloudflare_enabled',
        'wordpress_docker_tag' => 'coolify_wordpress_docker_tag',
        'wordpress_management_queue' => 'coolify_wordpress_management_queue',
        'wordpress_redis_enabled' => 'coolify_wordpress_redis_enabled',
        'wordpress_redis_host' => 'coolify_wordpress_redis_host',
        'wordpress_redis_port' => 'coolify_wordpress_redis_port',
    ],

    /** قوالب حماية/تسريع عند إنشاء موقع WordPress */
    'wordpress_security_presets' => [
        'basic' => 'أساسي — DNS + بروكسي + SSL + HTTPS',
        'performance' => 'أداء — أساسي + إعدادات كاش المتصفح',
        'strict' => 'صارم — أساسي + مستوى أمان أعلى',
    ],

    /** أنواع خدمة WordPress المدعومة في Coolify API */
    'wordpress_service_types' => [
        'wordpress-with-mariadb' => 'WordPress + MariaDB (موصى به)',
        'wordpress-with-mysql' => 'WordPress + MySQL',
        'wordpress-without-database' => 'WordPress بدون قاعدة بيانات',
    ],

    'defaults' => [
        'api_url' => '',
        'api_token' => '',
        'timeout' => 30,
        'ssh_user' => 'root',
        'ssh_private_key' => '',
        'ssh_private_key_path' => '',
        'ssh_key_cache_path' => 'coolify-keys',
        'ssh_host_fallback' => env('COOLIFY_SSH_HOST', ''),
        'ssh_port' => (int) env('COOLIFY_SSH_PORT', 22),
        'backup_queue' => 'coolify-backups',
        'snapshot_storage_config_id' => '',
        'coolify_s3_storage_uuid' => '',
        's3_prefix' => 'coolify-snapshots',
        'wordpress_base_domain' => '',
        'wordpress_default_server_uuid' => '',
        'wordpress_shared_project_uuid' => '',
        'wordpress_default_environment' => 'production',
        'wordpress_instant_deploy' => '1',
        'wordpress_provision_queue' => 'coolify-provision',
        'wordpress_default_destination_uuid' => '',
        'wordpress_service_type' => 'wordpress-with-mariadb',
        'wordpress_cloudflare_zone_id' => '',
        'wordpress_cloudflare_proxied' => '1',
        'wordpress_cloudflare_ssl_mode' => 'full',
        'wordpress_security_preset' => 'basic',
        'wordpress_cloudflare_enabled' => '1',
        'wordpress_docker_tag' => 'latest',
        'wordpress_management_queue' => 'coolify-provision',
        'wordpress_redis_enabled' => '0',
        'wordpress_redis_host' => '',
        'wordpress_redis_port' => '6379',
    ],

    /** سواقات مدعومة لتخزين لقطات volumes/manifest (S3-compatible) */
    'snapshot_storage_drivers' => [
        's3',
        'digitalocean',
        'wasabi',
        'backblaze',
        'cloudflare_r2',
    ],

    'metrics_refresh_seconds' => 10,
    'metrics_cache_seconds' => 8,

];
