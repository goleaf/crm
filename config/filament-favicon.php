<?php

declare(strict_types=1);

use Carbon\CarbonInterval;
use pxlrbt\FilamentFavicon\Drivers\DuckDuckGo;

return [
    /*
    |--------------------------------------------------------------------------
    | Favicon driver
    |--------------------------------------------------------------------------
    |
    | IconHorse offers higher fidelity but has request limits. DuckDuckGo is
    | free and unlimited, so we use it by default.
    |
    */
    'driver' => env('FILAMENT_FAVICON_DRIVER', DuckDuckGo::class),

    /*
    |--------------------------------------------------------------------------
    | Refresh cadence
    |--------------------------------------------------------------------------
    */
    'stale_after' => CarbonInterval::weeks(2),

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'disk' => env('FILAMENT_FAVICON_DISK', 'public'),
        'directory' => env('FILAMENT_FAVICON_DIRECTORY', 'favicons'),
    ],
];
