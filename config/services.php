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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    
    'whatsapp' => [
        'url' => env('WHATSAPP_API_URL', 'https://api.fonnte.com/send'),
        'token' => env('WHATSAPP_API_TOKEN'),
        'test_to' => env('WHATSAPP_TEST_TO'),
    ],

    'exchange_rates' => [
        'fallback_usd' => env('EXCHANGE_FALLBACK_USD', 16000),
        'fallback_cny' => env('EXCHANGE_FALLBACK_CNY', 2250),
    ],

    'lead_register_api_token' => env('LEAD_REGISTER_API_TOKEN'),

    'google_sheets' => [
        'service_account_path' => env('GOOGLE_SHEETS_SERVICE_ACCOUNT_PATH') ?: storage_path('app/google/service-account.json'),
        'spreadsheet_id' => env('GOOGLE_SHEETS_SATISFACTION_SURVEY_ID'),
    ],
];
