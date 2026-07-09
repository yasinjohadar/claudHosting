<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp / Evolution API Configuration
    |--------------------------------------------------------------------------
    */

    'timeout' => env('WHATSAPP_TIMEOUT', 30),

    'evolution_connect_timeout' => env('EVOLUTION_API_CONNECT_TIMEOUT', 30),

    'retry_attempts' => env('WHATSAPP_RETRY_ATTEMPTS', 3),

    'auto_reply' => env('WHATSAPP_AUTO_REPLY', false),

    'auto_reply_message' => env('WHATSAPP_AUTO_REPLY_MESSAGE', 'شكراً لك، تم استلام رسالتك. سنرد عليك قريباً.'),

    'webhook_path' => env('WHATSAPP_WEBHOOK_PATH', '/api/webhooks/evolution'),
];
