<?php

/**
 * Coolify settings — صفحات منفصلة (كل الإعدادات من لوحة التحكم فقط).
 */
return [

    'api' => [
        'label' => 'اتصال API',
        'icon' => 'fe fe-link',
        'color' => 'primary',
        'description' => 'عنوان Coolify ورمز Bearer ومهلة الطلبات',
        'partial' => 'tab-api',
        'show_api_test' => true,
        'rules' => [
            'api_url' => 'required|url|max:500',
            'api_token' => 'nullable|string|max:2000',
            'timeout' => 'nullable|integer|min:5|max:120',
        ],
    ],

    'backups' => [
        'label' => 'النسخ واللقطات',
        'icon' => 'fe fe-hard-drive',
        'color' => 'warning',
        'description' => 'S3، طوابير النسخ، وبادئة التخزين',
        'partial' => 'tab-backups',
        'rules' => [
            'backup_queue' => 'nullable|string|max:64|regex:/^[a-zA-Z0-9_\-]+$/',
            'snapshot_storage_config_id' => 'nullable|integer|min:1',
            'coolify_s3_storage_uuid' => 'nullable|string|max:255',
            's3_prefix' => 'nullable|string|max:255',
        ],
    ],

    'wordpress' => [
        'label' => 'WordPress',
        'icon' => 'fe fe-globe',
        'color' => 'success',
        'description' => 'النطاق، السيرفر، المشاريع، Filebrowser',
        'partial' => 'tab-wordpress',
        'rules' => [
            'wordpress_base_domain' => 'nullable|string|max:255',
            'wordpress_default_server_uuid' => 'nullable|string|max:255',
            'wordpress_shared_project_uuid' => 'nullable|string|max:255',
            'wordpress_default_environment' => 'nullable|string|max:64',
            'wordpress_instant_deploy' => 'nullable|boolean',
            'wordpress_provision_queue' => 'nullable|string|max:64|regex:/^[a-zA-Z0-9_\-]+$/',
            'wordpress_default_destination_uuid' => 'nullable|string|max:255',
            'wordpress_service_type' => 'nullable|string|in:wordpress-with-mariadb,wordpress-with-mysql,wordpress-without-database',
            'wordpress_custom_domain_enabled' => 'nullable|boolean',
            'wordpress_filebrowser_enabled' => 'nullable|boolean',
            'wordpress_filebrowser_subdomain_prefix' => 'nullable|string|max:32|regex:/^[a-z0-9-]+$/',
            'wordpress_filebrowser_subdomain_style' => 'nullable|string|in:flat,nested',
            'wordpress_filebrowser_open_mode' => 'nullable|string|in:embed,new_tab',
            'wordpress_filebrowser_admin_username' => 'nullable|string|max:32|regex:/^[a-z0-9_-]+$/',
            'wordpress_filebrowser_password_length' => 'nullable|integer|min:12|max:64',
        ],
    ],

    'cloudflare' => [
        'label' => 'Cloudflare',
        'icon' => 'fe fe-shield',
        'color' => 'info',
        'description' => 'Zone، SSL، البروكسي، وقوالب الحماية',
        'partial' => 'tab-cloudflare',
        'rules' => [
            'wordpress_cloudflare_enabled' => 'nullable|boolean',
            'wordpress_cloudflare_zone_id' => 'nullable|string|max:64',
            'wordpress_cloudflare_proxied' => 'nullable|boolean',
            'wordpress_cloudflare_ssl_mode' => 'nullable|string|in:off,flexible,full,strict',
            'wordpress_security_preset' => 'nullable|string|in:basic,performance,strict',
        ],
    ],

    'wp-cli' => [
        'label' => 'إدارة WP-CLI',
        'icon' => 'fe fe-terminal',
        'color' => 'purple',
        'description' => 'Docker tag، طابور الإدارة، Redis',
        'partial' => 'tab-wp-management',
        'rules' => [
            'wordpress_docker_tag' => 'nullable|string|max:128',
            'wordpress_management_queue' => 'nullable|string|max:64|regex:/^[a-zA-Z0-9_\-]+$/',
            'wordpress_redis_enabled' => 'nullable|boolean',
            'wordpress_redis_host' => 'nullable|string|max:255',
            'wordpress_redis_port' => 'nullable|integer|min:1|max:65535',
        ],
    ],

    'ssh' => [
        'label' => 'SSH',
        'icon' => 'fe fe-lock',
        'color' => 'teal',
        'description' => 'مفتاح SSH، المستخدم، المنفذ، وعنوان السيرفر',
        'partial' => 'tab-ssh',
        'show_ssh_test' => true,
        'sync_terminal_runtime' => true,
        'rules' => [
            'ssh_host_fallback' => 'required|string|max:255',
            'ssh_port' => 'nullable|integer|min:1|max:65535',
            'ssh_user' => 'nullable|string|max:64',
            'ssh_private_key' => 'nullable|string|max:10000',
            'ssh_private_key_path' => 'nullable|string|max:500',
        ],
    ],

    'terminal' => [
        'label' => 'Terminal Bridge',
        'icon' => 'fe fe-monitor',
        'color' => 'secondary',
        'description' => 'جسر WebSocket للطرفية التفاعلية (WordPress + VPS)',
        'partial' => 'tab-terminal-bridge',
        'show_terminal_test' => true,
        'sync_terminal_runtime' => true,
        'rules' => [
            'terminal_bridge_enabled' => 'nullable|boolean',
            'terminal_bridge_url' => 'nullable|url|max:500',
            'terminal_bridge_secret' => 'nullable|string|max:2000',
            'terminal_bridge_port' => 'nullable|integer|min:1|max:65535',
            'terminal_bridge_token_ttl' => 'nullable|integer|min:60|max:86400',
        ],
    ],

    'tab_aliases' => [
        'api' => 'api',
        'backups' => 'backups',
        'wordpress' => 'wordpress',
        'cloudflare' => 'cloudflare',
        'wp' => 'wp-cli',
        'wp-cli' => 'wp-cli',
        'ssh' => 'ssh',
        'terminal' => 'terminal',
    ],

];
