<?php

declare(strict_types=1);

return [
    'source_locale' => env('AUTO_TRANSLATE_SOURCE_LOCALE', env('APP_LOCALE', 'en')),

    'storage_path' => env('AUTO_TRANSLATE_STORAGE_PATH', storage_path('app/auto-translations')),

    'remote' => [
        'enabled' => env('AUTO_TRANSLATE_REMOTE_VIEW_ENABLED', false),
        'token' => env('AUTO_TRANSLATE_REMOTE_VIEW_TOKEN', ''),
    ],

    'paths' => [
        base_path('app'),
        base_path('app-modules/SystemAdmin/src'),
        base_path('app-modules/Documentation/src'),
        base_path('app-modules/OnboardSeed/src'),
        base_path('routes'),
        resource_path('views'),
        resource_path('js'),
    ],

    'ai' => [
        'enabled' => (bool) env('AUTO_TRANSLATE_ENABLED', false),
        'api_key' => env('AUTO_TRANSLATE_ANTHROPIC_API_KEY', ''),
        'api_base' => env('AUTO_TRANSLATE_ANTHROPIC_API_BASE', 'https://api.anthropic.com/v1'),
        'model' => env('AUTO_TRANSLATE_ANTHROPIC_MODEL', 'claude-3-haiku-20240307'),
        'temp' => (float) env('AUTO_TRANSLATE_TEMPERATURE', 0.3),
        'chunk' => (int) env('AUTO_TRANSLATE_CHUNK_SIZE', 50),
    ],
];
