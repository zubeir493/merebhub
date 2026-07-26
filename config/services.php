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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'chapa' => [
        'api_url' => env('CHAPA_API_URL', 'https://api.chapa.co/v1'),
        'secret_key' => env('CHAPA_SECRET_KEY'),
        'webhook_secret' => env('CHAPA_WEBHOOK_SECRET'),
        'timeout' => env('CHAPA_TIMEOUT', 15),
    ],

    'keygen' => [
        'api_url' => env('KEYGEN_API_URL', 'https://api.keygen.sh'),
        'api_token' => env('KEYGEN_API_TOKEN'),
        'account_id' => env('KEYGEN_ACCOUNT_ID'),
        'policy_id' => env('KEYGEN_POLICY_ID'),
        'timeout' => env('KEYGEN_TIMEOUT', 15),
    ],

];
