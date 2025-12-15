<?php

declare(strict_types=1);

return [
    'defaults' => [
        'timeout' => (float) env('HTTP_CLIENT_TIMEOUT', 8.0),
        'connect_timeout' => (float) env('HTTP_CLIENT_CONNECT_TIMEOUT', 2.0),
        'retry' => [
            'times' => (int) env('HTTP_CLIENT_RETRY_TIMES', 2),
            'sleep_ms' => (int) env('HTTP_CLIENT_RETRY_SLEEP_MS', 200),
        ],
        'headers' => [],
    ],

    'services' => [
        'github' => [
            'base_url' => env('GITHUB_HTTP_BASE_URL', 'https://api.github.com'),
            'token' => env('GITHUB_HTTP_TOKEN'),
            'timeout' => (float) env('GITHUB_HTTP_TIMEOUT', 6.0),
            'connect_timeout' => (float) env('GITHUB_HTTP_CONNECT_TIMEOUT', 2.5),
            'cache_minutes' => (int) env('GITHUB_HTTP_CACHE_MINUTES', 15),
            'retry' => [
                'times' => (int) env('GITHUB_HTTP_RETRY_TIMES', 3),
                'sleep_ms' => (int) env('GITHUB_HTTP_RETRY_SLEEP_MS', 250),
            ],
            'headers' => [
                'Accept' => 'application/vnd.github+json',
            ],
        ],
    ],
];
