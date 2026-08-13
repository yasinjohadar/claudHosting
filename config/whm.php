<?php

return [

    'keys' => [
        'host' => 'whm_host',
        'username' => 'whm_username',
        'api_token' => 'whm_api_token',
        'verify_ssl' => 'whm_verify_ssl',
        'default_package' => 'whm_default_package',
        'default_domain_suffix' => 'whm_default_domain_suffix',
        'timeout' => 'whm_timeout',
        'renewal_amount' => 'whm_renewal_amount',
        'invoice_due_days' => 'whm_invoice_due_days',
        'subscription_years' => 'whm_subscription_years',
        'ssh_host' => 'whm_ssh_host',
        'ssh_user' => 'whm_ssh_user',
        'ssh_port' => 'whm_ssh_port',
        'ssh_private_key' => 'whm_ssh_private_key',
        'ssh_private_key_path' => 'whm_ssh_private_key_path',
    ],

    'defaults' => [
        'host' => '',
        'username' => 'root',
        'api_token' => '',
        'verify_ssl' => '1',
        'default_package' => 'default',
        'default_domain_suffix' => '',
        'timeout' => '60',
        'renewal_amount' => '0',
        'invoice_due_days' => '7',
        'subscription_years' => '1',
        'ssh_host' => '',
        'ssh_user' => 'root',
        'ssh_port' => '22',
        'ssh_private_key' => '',
        'ssh_private_key_path' => '',
    ],

    /** Queue name for WHM WordPress management jobs */
    'wordpress_management_queue' => env('WHM_WORDPRESS_MANAGEMENT_QUEUE', 'default'),

    /** Cache for WHM server status (load, memory, disks) in seconds */
    'server_status_cache_seconds' => (int) env('WHM_SERVER_STATUS_CACHE', 60),

];
