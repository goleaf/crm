<?php

declare(strict_types=1);

use App\Support\Env;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue supports a variety of backends via a single, unified
    | API, giving you convenient access to each backend using identical
    | syntax for each. The default queue connection is defined below.
    |
    */

    'default' => Env::make()->queueConnection(),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection options for every queue backend
    | used by your application. An example configuration is provided for
    | each backend supported by Laravel. You're also free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => Env::make()->dbQueueConnection(),
            'table' => Env::make()->dbQueueTable(),
            'queue' => Env::make()->dbQueue(),
            'retry_after' => Env::make()->dbQueueRetryAfter(),
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => Env::make()->beanstalkdQueueHost(),
            'queue' => Env::make()->beanstalkdQueue(),
            'retry_after' => Env::make()->beanstalkdQueueRetryAfter(),
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => Env::make()->awsAccessKeyId(),
            'secret' => Env::make()->awsSecretAccessKey(),
            'prefix' => Env::make()->sqsPrefix(),
            'queue' => Env::make()->sqsQueue(),
            'suffix' => Env::make()->sqsSuffix(),
            'region' => Env::make()->awsDefaultRegion(),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => Env::make()->redisQueueConnection(),
            'queue' => Env::make()->redisQueue(),
            'retry_after' => Env::make()->redisQueueRetryAfter(),
            'block_for' => null,
            'after_commit' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    |
    | The following options configure the database and table that store job
    | batching information. These options can be updated to any database
    | connection and table which has been defined by your application.
    |
    */

    'batching' => [
        'database' => Env::make()->dbConnection(),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control how and where failed jobs are stored. Laravel ships with
    | support for storing failed jobs in a simple file or in a database.
    |
    | Supported drivers: "database-uuids", "dynamodb", "file", "null"
    |
    */

    'failed' => [
        'driver' => Env::make()->queueFailedDriver(),
        'database' => Env::make()->dbConnection(),
        'table' => 'failed_jobs',
    ],

];
