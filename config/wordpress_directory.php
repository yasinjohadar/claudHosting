<?php

return [

    'plugins_api_url' => 'https://api.wordpress.org/plugins/info/1.2/',

    'themes_api_url' => 'https://api.wordpress.org/themes/info/1.2/',

    'per_page' => (int) env('WORDPRESS_DIRECTORY_PER_PAGE', 24),

    'max_page' => 20,

    'cache_ttl' => (int) env('WORDPRESS_DIRECTORY_CACHE_TTL', 1800),

    'timeout' => (int) env('WORDPRESS_DIRECTORY_TIMEOUT', 25),

    'slug_pattern' => '/^[a-z0-9\-]+$/',

];
