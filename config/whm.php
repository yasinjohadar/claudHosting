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
    ],

    'defaults' => [
        'host' => '',
        'username' => 'root',
        'api_token' => '',
        'verify_ssl' => '1',
        'default_package' => 'default',
        'default_domain_suffix' => '',
        'timeout' => '60',
    ],

    /** Cache for WHM server status (load, memory, disks) in seconds */
    'server_status_cache_seconds' => (int) env('WHM_SERVER_STATUS_CACHE', 60),

];
