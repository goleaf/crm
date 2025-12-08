<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Unsplash API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your Unsplash API credentials and default settings.
    | Get your API keys from: https://unsplash.com/oauth/applications
    |
    */

    'access_key' => env('UNSPLASH_ACCESS_KEY'),
    'secret_key' => env('UNSPLASH_SECRET_KEY'),
    'utm_source' => env('UNSPLASH_UTM_SOURCE', config('app.name')),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Configuration
    |--------------------------------------------------------------------------
    |
    | Configure HTTP client behavior for Unsplash API requests.
    | Uses the centralized HTTP client configuration pattern.
    |
    */

    'http' => [
        'timeout' => (int) env('UNSPLASH_HTTP_TIMEOUT', 30),
        'retry' => [
            'times' => (int) env('UNSPLASH_HTTP_RETRY_TIMES', 3),
            'sleep' => (int) env('UNSPLASH_HTTP_RETRY_SLEEP', 1000),
        ],
        'base_url' => env('UNSPLASH_API_BASE_URL', 'https://api.unsplash.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Image Settings
    |--------------------------------------------------------------------------
    |
    | Configure default image quality, size, and download behavior.
    |
    */

    'defaults' => [
        'per_page' => (int) env('UNSPLASH_DEFAULT_PER_PAGE', 30),
        'orientation' => env('UNSPLASH_DEFAULT_ORIENTATION'), // landscape, portrait, squarish
        'quality' => (int) env('UNSPLASH_DEFAULT_QUALITY', 80),
        'auto_download' => (bool) env('UNSPLASH_AUTO_DOWNLOAD', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure where downloaded Unsplash images should be stored.
    |
    */

    'storage' => [
        'disk' => env('UNSPLASH_STORAGE_DISK', 'public'),
        'path' => env('UNSPLASH_STORAGE_PATH', 'unsplash'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    |
    | Configure database table names for Unsplash assets.
    |
    */

    'tables' => [
        'assets' => env('UNSPLASH_ASSETS_TABLE', 'unsplash_assets'),
        'pivot' => env('UNSPLASH_PIVOT_TABLE', 'unsplashables'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for Unsplash API responses to reduce API calls.
    |
    */

    'cache' => [
        'enabled' => (bool) env('UNSPLASH_CACHE_ENABLED', true),
        'ttl' => (int) env('UNSPLASH_CACHE_TTL', 3600), // 1 hour
        'prefix' => env('UNSPLASH_CACHE_PREFIX', 'unsplash'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament Integration
    |--------------------------------------------------------------------------
    |
    | Configure Filament-specific settings for the Unsplash picker.
    |
    */

    'filament' => [
        'enabled' => (bool) env('UNSPLASH_FILAMENT_ENABLED', true),
        'modal_width' => env('UNSPLASH_FILAMENT_MODAL_WIDTH', 'xl'),
        'columns_grid' => (int) env('UNSPLASH_FILAMENT_COLUMNS_GRID', 3),
        'show_photographer' => (bool) env('UNSPLASH_FILAMENT_SHOW_PHOTOGRAPHER', true),
    ],
];
