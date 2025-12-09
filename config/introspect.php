<?php

declare(strict_types=1);

$moduleSources = array_map(
    static fn (string $path): string => trim(str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path), DIRECTORY_SEPARATOR),
    glob(base_path('app-modules/*/src')) ?: [],
);

return [
    'directories' => array_values(array_unique(array_merge(
        ['app'],
        $moduleSources,
    ))),

    'namespaces' => [
        'App\\',
        'Relaticle\\',
    ],

    'cache' => [
        'key' => 'introspect.snapshot',
        'ttl' => (int) env('INTROSPECT_CACHE_TTL', 300),
    ],

    'export' => [
        'path' => storage_path('app/introspection/snapshot.json'),
    ],
];
