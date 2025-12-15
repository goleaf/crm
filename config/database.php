<?php

declare(strict_types=1);

use App\Support\Env;

$mysqlAttrSslCa = defined('Pdo\\Mysql::ATTR_SSL_CA')
    ? Pdo\Mysql::ATTR_SSL_CA
    : PDO::MYSQL_ATTR_SSL_CA;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => Env::make()->dbConnection(),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => Env::make()->dbUrl(),
            'database' => Env::make()->dbDatabase(),
            'prefix' => '',
            'foreign_key_constraints' => Env::make()->dbForeignKeys(),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => Env::make()->dbUrl(),
            'host' => Env::make()->dbHost(),
            'port' => Env::make()->dbPort(),
            'database' => Env::make()->dbDatabase(),
            'username' => Env::make()->dbUsername(),
            'password' => Env::make()->dbPassword(),
            'unix_socket' => Env::make()->dbSocket(),
            'charset' => Env::make()->dbCharset(),
            'collation' => Env::make()->dbCollation(),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                $mysqlAttrSslCa => Env::make()->mysqlAttrSslCa(),
            ]) : [],
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => Env::make()->dbUrl(),
            'host' => Env::make()->dbHost(),
            'port' => Env::make()->dbPort(),
            'database' => Env::make()->dbDatabase(),
            'username' => Env::make()->dbUsername(),
            'password' => Env::make()->dbPassword(),
            'unix_socket' => Env::make()->dbSocket(),
            'charset' => Env::make()->dbCharset(),
            'collation' => Env::make()->dbCollation(),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                $mysqlAttrSslCa => Env::make()->mysqlAttrSslCa(),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => Env::make()->dbUrl(),
            'host' => Env::make()->dbHost(),
            'port' => Env::make()->dbPort(),
            'database' => Env::make()->dbDatabase(),
            'username' => Env::make()->dbUsername(),
            'password' => Env::make()->dbPassword(),
            'charset' => Env::make()->dbCharset(),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => Env::make()->dbUrl(),
            'host' => Env::make()->dbHost(),
            'port' => Env::make()->dbPort(),
            'database' => Env::make()->dbDatabase(),
            'username' => Env::make()->dbUsername(),
            'password' => Env::make()->dbPassword(),
            'charset' => Env::make()->dbCharset(),
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => Env::make()->redisClient(),

        'options' => [
            'cluster' => Env::make()->redisCluster(),
            'prefix' => Env::make()->redisPrefix(),
        ],

        'default' => [
            'url' => Env::make()->redisUrl(),
            'host' => Env::make()->redisHost(),
            'username' => Env::make()->redisUsername(),
            'password' => Env::make()->redisPassword(),
            'port' => Env::make()->redisPort(),
            'database' => Env::make()->redisDb(),
        ],

        'cache' => [
            'url' => Env::make()->redisUrl(),
            'host' => Env::make()->redisHost(),
            'username' => Env::make()->redisUsername(),
            'password' => Env::make()->redisPassword(),
            'port' => Env::make()->redisPort(),
            'database' => Env::make()->redisCacheDb(),
        ],

    ],

];
