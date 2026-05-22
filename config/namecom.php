<?php

return [

    'keys' => [
        'username' => 'namecom_api_username',
        'api_token' => 'namecom_api_token',
        'api_base' => 'namecom_api_base',
        'timeout' => 'namecom_api_timeout',
        'cache_ttl' => 'namecom_cache_ttl',
    ],

    'defaults' => [
        'username' => '',
        'api_token' => '',
        'api_base' => 'https://api.name.com/v4',
        'timeout' => 30,
        'cache_ttl' => 600,
    ],

    'env_fallback' => [
        'username' => '',
        'api_token' => '',
        'api_base' => 'https://api.name.com/v4',
        'timeout' => 30,
        'cache_ttl' => 600,
    ],

    'sandbox_api_base' => 'https://api.dev.name.com/v4',

];
