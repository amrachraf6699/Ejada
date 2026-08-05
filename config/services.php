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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'quran' => [
        'base_url' => env('QURAN_API_BASE_URL', 'https://api.alquran.cloud/v1'),
        'client_id' => env('QURAN_API_CLIENT_ID'),
        'client_secret' => env('QURAN_API_CLIENT_SECRET'),
        'token_url' => env('QURAN_API_TOKEN_URL', 'https://oauth.quran.foundation/oauth2/token'),
        'timeout' => (int) env('QURAN_API_TIMEOUT', 8),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        'timeout' => (int) env('TELEGRAM_TIMEOUT', 30),
    ],

];
