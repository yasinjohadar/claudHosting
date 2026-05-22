<?php

return [

    'keys' => [
        'api_token' => 'cloudflare_api_token',
        'account_id' => 'cloudflare_account_id',
        'timeout' => 'cloudflare_api_timeout',
        'cache_ttl' => 'cloudflare_cache_ttl',
    ],

    'defaults' => [
        'api_token' => '',
        'account_id' => '',
        'timeout' => 30,
        'cache_ttl' => 600,
    ],

    'env_fallback' => [
        'api_token' => env('CLOUDFLARE_API_TOKEN', ''),
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID', ''),
        'timeout' => (int) env('CLOUDFLARE_API_TIMEOUT', 30),
        'cache_ttl' => (int) env('CLOUDFLARE_CACHE_TTL', 600),
    ],

    'api_base' => 'https://api.cloudflare.com/client/v4',

];
