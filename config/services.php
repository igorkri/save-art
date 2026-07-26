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

    'liqpay' => [
        'public_key' => env('LIQPAY_PUBLIC_KEY'),
        'private_key' => env('LIQPAY_PRIVATE_KEY'),
        'sandbox' => env('LIQPAY_SANDBOX', true),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Key for Frontend Applications
    |--------------------------------------------------------------------------
    |
    | Цей ключ використовується для захисту публічних API endpoints.
    | Фронтенд має передавати його у заголовку X-Api-Key.
    |
    */

    'api_key' => env('API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Отримувач заявок на співпрацю
    |--------------------------------------------------------------------------
    |
    | Адреса, на яку надходять заявки з форми "Заявка" (ApplicationModal —
    | кнопка "Відправити заявку" у футері).
    |
    */

    'application_email' => env('APPLICATION_EMAIL', 'idartua.bo@gmail.com'),

];
