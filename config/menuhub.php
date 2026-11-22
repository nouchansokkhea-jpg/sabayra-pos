<?php

return [
    'name' => env('APP_NAME', 'SABAYRA Cambodia'),
    'tagline' => 'Smarter Business, Simpler Every Day.',

    'locales' => [
        'en' => 'English',
        'km' => 'ភាសាខ្មែរ (Khmer)',
    ],
    'default_locale' => env('APP_LOCALE', 'en'),

    'currencies' => [
        'USD' => [
            'code' => 'USD',
            'symbol' => '$',
            'name' => 'US Dollar',
            'decimals' => 2,
        ],
        'KHR' => [
            'code' => 'KHR',
            'symbol' => '៛',
            'name' => 'Khmer Riel',
            'decimals' => 0,
        ],
    ],
    'default_currency' => env('DEFAULT_CURRENCY', 'USD'),
    'default_exchange_rate_khr' => (float) env('DEFAULT_EXCHANGE_RATE_KHR', 4100),
    'qr_base_url' => env('QR_BASE_URL', null),

    /*
    | Supported Business Categories for Cambodia & International
    */
    'business_types' => [
        'restaurant' => [
            'name_en' => 'Restaurant',
            'name_km' => 'ភោជនីយដ្ឋាន',
            'icon' => 'utensils',
            'has_tables' => true,
            'has_recipes' => true,
        ],
        'cafe' => [
            'name_en' => 'Café',
            'name_km' => 'ហាងកាហ្វេ',
            'icon' => 'coffee',
            'has_tables' => true,
            'has_recipes' => true,
        ],
        'milk_tea' => [
            'name_en' => 'Milk Tea Shop',
            'name_km' => 'ហាងតែគុជ',
            'icon' => 'cup-soda',
            'has_tables' => true,
            'has_recipes' => true,
        ],
        'bakery' => [
            'name_en' => 'Bakery',
            'name_km' => 'ហាងនំបុ័ង / នំខេក',
            'icon' => 'cake',
            'has_tables' => false,
            'has_recipes' => true,
        ],
        'fast_food' => [
            'name_en' => 'Fast Food',
            'name_km' => 'អាហាររហ័ស',
            'icon' => 'burger',
            'has_tables' => true,
            'has_recipes' => true,
        ],
        'supermarket' => [
            'name_en' => 'Supermarket',
            'name_km' => 'ផ្សារទំនើប',
            'icon' => 'shopping-cart',
            'has_tables' => false,
            'has_recipes' => false,
        ],
        'mini_mart' => [
            'name_en' => 'Mini Mart',
            'name_km' => 'មីនីម៉ាត',
            'icon' => 'store',
            'has_tables' => false,
            'has_recipes' => false,
        ],
        'retail_shop' => [
            'name_en' => 'Retail Shop',
            'name_km' => 'ហាងលក់រាយទូទៅ',
            'icon' => 'shopping-bag',
            'has_tables' => false,
            'has_recipes' => false,
        ],
        'clothing' => [
            'name_en' => 'Clothing & Fashion Store',
            'name_km' => 'ហាងសម្លៀកបំពាក់',
            'icon' => 'shirt',
            'has_tables' => false,
            'has_recipes' => false,
        ],
        'beauty' => [
            'name_en' => 'Beauty & Cosmetics',
            'name_km' => 'ហាងសម្អាង / គ្រឿងសម្អាង',
            'icon' => 'sparkles',
            'has_tables' => false,
            'has_recipes' => false,
        ],
        'services' => [
            'name_en' => 'Services Business',
            'name_km' => 'អាជីវកម្មសេវាកម្ម',
            'icon' => 'briefcase',
            'has_tables' => false,
            'has_recipes' => false,
        ],
        'other' => [
            'name_en' => 'Other Business',
            'name_km' => 'អាជីវកម្មផ្សេងៗ',
            'icon' => 'building',
            'has_tables' => false,
            'has_recipes' => false,
        ],
    ],
];
