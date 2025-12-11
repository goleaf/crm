<?php

declare(strict_types=1);

echo "Starting bootstrap test...\n";

// Try to bootstrap Laravel
try {
    echo "Loading autoloader...\n";
    require __DIR__ . '/vendor/autoload.php';

    echo "Creating application...\n";
    $app = require __DIR__ . '/bootstrap/app.php';

    echo "Making kernel...\n";
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

    echo "Bootstrapping kernel...\n";
    $kernel->bootstrap();

    echo "Bootstrap successful!\n";

    // Try to run a simple database query
    echo "Testing database connection...\n";
    $users = DB::table('users')->count();
    echo "Users count: $users\n";

} catch (Exception $e) {
    echo 'Error during bootstrap: ' . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "Bootstrap test complete.\n";
