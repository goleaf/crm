<?php

declare(strict_types=1);

namespace Tests\Support;

use Tests\TestCase;

/**
 * Base test case for audit tests that don't require database access.
 * 
 * This test case extends the base TestCase but doesn't use RefreshDatabase
 * to avoid transaction issues with PHP 8.4 + SQLite when testing
 * configuration-only functionality.
 */
abstract class AuditTestCase extends TestCase
{
    // No RefreshDatabase trait - audit tests don't need database access
}