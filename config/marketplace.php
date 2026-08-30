<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Marketplace platform settings
    |--------------------------------------------------------------------------
    |
    | These values keep platform-specific choices in configuration so domain
    | actions do not read environment variables directly.
    |
    */

    'public_media_disk' => env('MARKETPLACE_PUBLIC_MEDIA_DISK', 'public'),

    'private_files_disk' => env('MARKETPLACE_PRIVATE_FILES_DISK', 'private'),

    'audit' => [
        'sensitive_keys' => [
            'password',
            'password_confirmation',
            'token',
            'access_token',
            'refresh_token',
            'secret',
            'api_key',
            'api_secret',
            'authorization',
            'license_key',
            'magic_token',
            'email',
            'phone',
            'billing_address',
            'tax_identifier',
            'webhook_payload',
        ],
    ],

    'observability' => [
        'correlation_header' => env('CORRELATION_ID_HEADER', 'X-Correlation-ID'),
    ],

];
