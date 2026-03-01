<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Cloud API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for WhatsApp Cloud API (Meta) integration
    |
    */

    'api_version' => env('WHATSAPP_CLOUD_API_VERSION', 'v20.0'),

    'base_url' => 'https://graph.facebook.com',

    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),

    'waba_id' => env('WHATSAPP_WABA_ID'),

    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),

    'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),

    'app_secret' => env('WHATSAPP_APP_SECRET'),

    'webhook_path' => env('WHATSAPP_WEBHOOK_PATH', '/api/webhooks/whatsapp'),

    'default_from' => env('WHATSAPP_DEFAULT_FROM'),

    'strict_signature' => env('WHATSAPP_STRICT_SIGNATURE', true),

    'auto_reply' => env('WHATSAPP_AUTO_REPLY', false),

    'auto_reply_message' => env('WHATSAPP_AUTO_REPLY_MESSAGE', 'شكراً لك، تم استلام رسالتك. سنرد عليك قريباً.'),

    'timeout' => env('WHATSAPP_TIMEOUT', 30),

    'retry_attempts' => env('WHATSAPP_RETRY_ATTEMPTS', 3),
];


