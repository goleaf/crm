<?php

declare(strict_types=1);

// config for Inerba/DbConfig
return [

    /*
    |--------------------------------------------------------------------------
    | Table Name
    |--------------------------------------------------------------------------
    |
    | The name of the database table used to store the settings.
    |
    */
    'table_name' => env('DB_CONFIG_TABLE', 'db_config'),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the cache behavior for the package. You can set a custom
    | prefix for all cache keys and define a Time To Live (TTL) in minutes.
    |
    | Setting 'ttl' to null or 0 will cache the settings forever.
    |
    */
    'cache' => [
        'prefix' => env('DB_CONFIG_CACHE_PREFIX', 'db-config'),
        'ttl' => env('DB_CONFIG_CACHE_TTL') !== null ? (int) env('DB_CONFIG_CACHE_TTL') : null, // Cache forever by default
    ],

];
