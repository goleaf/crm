<?php

declare(strict_types=1);

return [
    'pagination' => [
        'default_per_page' => env('PERFORMANCE_PAGINATION_DEFAULT', 25),
        'max_per_page' => env('PERFORMANCE_PAGINATION_MAX', 100),
        'parameter' => env('PERFORMANCE_PAGINATION_PARAMETER', 'per_page'),
    ],
    'query' => [
        'slow_query_threshold_ms' => env('PERFORMANCE_SLOW_QUERY_THRESHOLD_MS', 750),
    ],
    'lazy_loading' => [
        'prevent' => env('PERFORMANCE_PREVENT_LAZY_LOADING', false),
        'strict_mode' => env('PERFORMANCE_MODEL_STRICT_MODE', false),
    ],
    'assets' => [
        'minify' => env('PERFORMANCE_MINIFY_ASSETS', true),
        'cdn_enabled' => env('PERFORMANCE_CDN_ENABLED', false),
    ],
    'cache' => [
        'ttl' => env('PERFORMANCE_CACHE_TTL', 3600),
    ],
    'memory' => [
        'limit_mb' => env('PERFORMANCE_MEMORY_LIMIT_MB', 512),
    ],
    'diagnostics' => [
        'enabled' => env('PERFORMANCE_DIAGNOSTICS_ENABLED', true),
    ],
];
