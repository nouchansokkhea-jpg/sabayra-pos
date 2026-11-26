<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Supabase, AWS, and more.
    |
    */

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'supabase' => [
        'url' => env('SUPABASE_URL'),
        'anon_key' => env('SUPABASE_ANON_KEY'),
        'service_key' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'bucket' => env('SUPABASE_STORAGE_BUCKET', 'menuhub-assets'),
        'verify_ssl' => env('SUPABASE_VERIFY_SSL', false),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME', 'SabayraBot'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'api_base_url' => env('TELEGRAM_API_BASE_URL', 'https://api.telegram.org'),
        'verification_ttl_minutes' => (int) env('TELEGRAM_VERIFICATION_TTL_MINUTES', 15),
        'verify_ssl' => filter_var(env('TELEGRAM_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),
        'polling_fallback' => filter_var(env('TELEGRAM_POLLING_FALLBACK', false), FILTER_VALIDATE_BOOLEAN),
    ],

    'bakong' => [
        'enabled' => filter_var(env('BAKONG_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'api_base_url' => env('BAKONG_API_BASE_URL', 'https://api-bakong.nbc.gov.kh'),
        'api_token' => env('BAKONG_OPEN_API_TOKEN'),
        'verify_ssl' => filter_var(env('BAKONG_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),
    ],

    'aba_payway' => [
        'enabled' => filter_var(env('ABA_PAYWAY_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'sandbox_url' => env('ABA_PAYWAY_SANDBOX_URL', 'https://checkout-sandbox.payway.com.kh'),
        'production_url' => env('ABA_PAYWAY_PRODUCTION_URL', 'https://checkout.payway.com.kh'),
        'verify_ssl' => filter_var(env('ABA_PAYWAY_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),
    ],

];
