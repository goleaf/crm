<?php

declare(strict_types=1);

use App\Support\Env;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache store that will be used by the
    | framework. This connection is utilized if another isn't explicitly
    | specified when running a cache operation inside the application.
    |
    */

    'default' => Env::make()->cacheStore(),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    | Supported drivers: "array", "database", "file", "memcached",
    |                    "redis", "dynamodb", "octane", "null"
    |
    */

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'connection' => Env::make()->dbCacheConnection(),
            'table' => Env::make()->dbCacheTable(),
            'lock_connection' => Env::make()->dbCacheLockConnection(),
            'lock_table' => Env::make()->dbCacheLockTable(),
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => Env::make()->memcachedPersistentId(),
            'sasl' => [
                Env::make()->memcachedUsername(),
                Env::make()->memcachedPassword(),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => Env::make()->memcachedHost(),
                    'port' => Env::make()->memcachedPort(),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => Env::make()->redisCacheConnection(),
            'lock_connection' => Env::make()->redisCacheLockConnection(),
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => Env::make()->awsAccessKeyId(),
            'secret' => Env::make()->awsSecretAccessKey(),
            'region' => Env::make()->awsDefaultRegion(),
            'table' => Env::make()->dynamodbCacheTable(),
            'endpoint' => Env::make()->dynamodbEndpoint(),
        ],

        'octane' => [
            'driver' => 'octane',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing the APC, database, memcached, Redis, and DynamoDB cache
    | stores, there might be other applications using the same cache. For
    | that reason, you may prefix every cache key to avoid collisions.
    |
    */

    'prefix' => Env::make()->cachePrefix(),

];
