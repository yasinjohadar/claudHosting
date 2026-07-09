<?php

return [

    'enabled' => env('PHONE_OTP_ENABLED', true),

    'code_length' => (int) env('PHONE_OTP_CODE_LENGTH', 6),

    'ttl_seconds' => (int) env('PHONE_OTP_TTL_SECONDS', 300),

    'max_attempts' => (int) env('PHONE_OTP_MAX_ATTEMPTS', 5),

    'resend_cooldown_seconds' => (int) env('PHONE_OTP_RESEND_COOLDOWN', 60),

    'rate_limit' => [
        'max_per_phone' => (int) env('PHONE_OTP_RATE_LIMIT_PHONE', 3),
        'window_minutes' => (int) env('PHONE_OTP_RATE_LIMIT_WINDOW', 15),
    ],

    'recent_verification_minutes' => (int) env('PHONE_OTP_RECENT_VERIFICATION_MINUTES', 15),

    'features' => [
        'register' => env('PHONE_OTP_REGISTER', true),
        'login' => env('PHONE_OTP_LOGIN', true),
        'reset_password' => env('PHONE_OTP_RESET_PASSWORD', true),
        'change_phone' => env('PHONE_OTP_CHANGE_PHONE', true),
    ],

];
