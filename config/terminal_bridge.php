<?php

return [

    'enabled' => (bool) env('TERMINAL_BRIDGE_ENABLED', false),

    'url' => rtrim((string) env('TERMINAL_BRIDGE_URL', 'http://127.0.0.1:3099'), '/'),

    'secret' => (string) env('TERMINAL_BRIDGE_SECRET', env('APP_KEY', '')),

    'token_ttl_seconds' => (int) env('TERMINAL_BRIDGE_TOKEN_TTL', 900),

];
