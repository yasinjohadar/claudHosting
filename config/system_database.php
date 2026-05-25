<?php

return [

    'cache_ttl_seconds' => (int) env('SYSTEM_DB_CACHE_TTL', 300),

    'allowed_connections' => null,

    'excluded_connections' => [],

    'excluded_tables' => [],

    'show_connection_host' => true,

];
