<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Foundation\Application;

// Keep Rector/Larastan bootstrap isolated from local infra (Redis, queues, etc.).
putenv('APP_ENV=testing');
putenv('CACHE_STORE=array');
putenv('SESSION_DRIVER=array');
putenv('QUEUE_CONNECTION=sync');
putenv('TELESCOPE_ENABLED=false');

if (! file_exists(__DIR__ . '/../bootstrap/app.php')) {
    return;
}

$app = require __DIR__ . '/../bootstrap/app.php';

if ($app instanceof Application) {
    $app->make(Kernel::class)->bootstrap();

    if (! defined('LARAVEL_VERSION')) {
        define('LARAVEL_VERSION', $app->version());
    }
}
