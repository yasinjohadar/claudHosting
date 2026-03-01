<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WHMCS API
    |--------------------------------------------------------------------------
    */
    'whmcs' => [
        'url' => env('WHMCS_API_URL'),
        'identifier' => env('WHMCS_API_IDENTIFIER'),
        'secret' => env('WHMCS_API_SECRET'),
        // مفتاح تجاوز قيد الـ IP (اختياري): أضفه في WHMCS configuration.php كـ $api_access_key
        'access_key' => env('WHMCS_API_ACCESS_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | cPanel
    |--------------------------------------------------------------------------
    | رابط لوحة تحكم cPanel (مثال: https://server.example.com:2083)
    | إذا كان الرابط يحتوي على :username سيُستبدل باسم المستخدم تلقائياً.
    */
    'cpanel' => [
        'url' => env('CPANEL_BASE_URL'),
    ],

];
