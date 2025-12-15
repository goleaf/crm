<?php

declare(strict_types=1);

return [
    'yaml_file' => storage_path('app/laravel-schema-docs.yaml'),

    'excluded_tables' => [
        'cache',
        'cache_locks',
        'failed_jobs',
        'job_batches',
        'jobs',
        'migrations',
        'password_reset_tokens',
        'personal_access_tokens',
        'sessions',
    ],

    'middleware' => [
        'web',
        'crm',
        'auth',
        'verified',
    ],

    'redirect_url' => env('SCHEMA_DOCS_REDIRECT_URL', '/'),

    'show_pages' => env('SHOW_SCHEMA_DOCS', true),
];
