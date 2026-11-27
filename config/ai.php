<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SABAYRA AI Center Master Configuration
    |--------------------------------------------------------------------------
    | Provider-independent AI routing, model defaults, token budgets,
    | timeouts, retries, and fallback credit quotas.
    */
    'enabled' => filter_var(env('AI_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'default_provider' => env('AI_DEFAULT_PROVIDER', 'gemini'),

    'default_model' => env('AI_DEFAULT_MODEL', 'gemini-2.5-flash'),

    'temperature' => (float) env('AI_TEMPERATURE', 0.4),

    'max_output_tokens' => (int) env('AI_MAX_OUTPUT_TOKENS', 2048),

    'timeout_seconds' => (int) env('AI_TIMEOUT_SECONDS', 30),

    'retry_attempts' => (int) env('AI_RETRY_ATTEMPTS', 2),

    'default_monthly_credits' => (int) env('AI_DEFAULT_MONTHLY_CREDITS', 100),

    'allow_business_override' => filter_var(env('AI_ALLOW_BUSINESS_OVERRIDE', false), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Configured AI Providers & Estimated Token Pricing (USD per 1M tokens)
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'gemini' => [
            'label' => 'Google Gemini',
            'base_url' => env('GEMINI_API_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'default_model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
            'models' => [
                'gemini-2.5-flash' => [
                    'label' => 'Gemini 2.5 Flash (Fast, Khmer-ready, Structured Tools)',
                    'input_per_1m' => 0.30,
                    'output_per_1m' => 2.50,
                ],
                'gemini-2.5-pro' => [
                    'label' => 'Gemini 2.5 Pro (Complex Reasoning & Analytics)',
                    'input_per_1m' => 1.25,
                    'output_per_1m' => 10.00,
                ],
                'gemini-2.0-flash' => [
                    'label' => 'Gemini 2.0 Flash',
                    'input_per_1m' => 0.10,
                    'output_per_1m' => 0.40,
                ],
            ],
            'verify_ssl' => filter_var(env('AI_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),
        ],

        'openai' => [
            'label' => 'OpenAI',
            'base_url' => env('OPENAI_API_BASE_URL', 'https://api.openai.com/v1'),
            'default_model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'models' => [
                'gpt-4o-mini' => [
                    'label' => 'GPT-4o mini (Fast, Tool Calling & JSON Schema)',
                    'input_per_1m' => 0.15,
                    'output_per_1m' => 0.60,
                ],
                'gpt-4o' => [
                    'label' => 'GPT-4o (Flagship Multimodal & Reasoning)',
                    'input_per_1m' => 2.50,
                    'output_per_1m' => 10.00,
                ],
            ],
            'verify_ssl' => filter_var(env('AI_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Credit Weights & Statistical Guards
    |--------------------------------------------------------------------------
    */
    'feature_credits' => [
        'assistant' => 1,
        'menu_creator' => 1,
        'marketing_studio' => 1,
        'inventory_forecast' => 2,
        'sales_prediction' => 2,
        'insights' => 2,
    ],

    'forecasting' => [
        'min_days_for_short_horizon' => 7,
        'min_days_for_monthly_horizon' => 14,
        'min_orders_required' => 5,
    ],
];
