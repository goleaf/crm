<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Code Coverage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for PCOV-based code coverage analysis.
    |
    */

    'coverage' => [
        /*
        | HTML coverage report directory
        */
        'html_dir' => env('COVERAGE_HTML_DIR', 'coverage-html'),

        /*
        | Clover XML coverage report file
        */
        'clover_file' => env('COVERAGE_CLOVER_FILE', 'coverage.xml'),

        /*
        | Cache TTL for coverage statistics (in seconds)
        */
        'cache_ttl' => env('COVERAGE_CACHE_TTL', 300),

        /*
        | Minimum coverage percentage threshold
        */
        'min_percentage' => env('COVERAGE_MIN_PERCENTAGE', 80),

        /*
        | Minimum type coverage percentage
        */
        'min_type_coverage' => env('COVERAGE_MIN_TYPE_COVERAGE', 99.9),

        /*
        | Enable PCOV
        */
        'pcov_enabled' => env('PCOV_ENABLED', true),

        /*
        | PCOV directory (base path for coverage)
        */
        'pcov_directory' => env('PCOV_DIRECTORY', '.'),

        /*
        | PCOV exclude pattern
        */
        'pcov_exclude' => env('PCOV_EXCLUDE', '~vendor~'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Suites
    |--------------------------------------------------------------------------
    |
    | Available test suites for selective testing.
    |
    */

    'suites' => [
        'feature' => 'Feature',
        'unit' => 'Unit',
        'arch' => 'Arch',
    ],

    /*
    |--------------------------------------------------------------------------
    | Parallel Testing
    |--------------------------------------------------------------------------
    |
    | Configuration for parallel test execution.
    |
    */

    'parallel' => [
        'enabled' => env('TEST_PARALLEL_ENABLED', true),
        'processes' => env('TEST_PARALLEL_PROCESSES', null), // null = auto-detect
    ],

    /*
    |--------------------------------------------------------------------------
    | Stress Testing
    |--------------------------------------------------------------------------
    |
    | Configuration for stress testing with Pest Stressless.
    |
    */

    'stress' => [
        'enabled' => env('RUN_STRESS_TESTS', false),
        'target' => env('STRESSLESS_TARGET', null),
        'concurrency' => env('STRESSLESS_CONCURRENCY', 10),
        'duration' => env('STRESSLESS_DURATION', 10),
        'p95_threshold_ms' => env('STRESSLESS_P95_THRESHOLD_MS', 500),
    ],
];
