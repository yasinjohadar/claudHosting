<?php

/**
 * قيم افتراضية لـ Terminal Bridge.
 * الإعدادات الفعلية تُضبط من لوحة التحكم → إعدادات Coolify → تبويب Terminal.
 * .env يُستخدم فقط كاحتياطي إذا لم تُحفظ قيمة في قاعدة البيانات بعد.
 */
return [

    'env_fallback' => [
        'enabled' => (bool) env('TERMINAL_BRIDGE_ENABLED', false),
        'url' => rtrim((string) env('TERMINAL_BRIDGE_URL', 'http://127.0.0.1:3099'), '/'),
        'secret' => (string) env('TERMINAL_BRIDGE_SECRET', ''),
        'port' => (int) env('TERMINAL_BRIDGE_PORT', 3099),
        'token_ttl_seconds' => (int) env('TERMINAL_BRIDGE_TOKEN_TTL', 900),
    ],

];
