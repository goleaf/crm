<?php

declare(strict_types=1);

namespace App\Extensions;

/**
 * Test handler for extension testing.
 */
final class TestHandler
{
    /**
     * Handle extension execution.
     *
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function handle(array $context): array
    {
        // Simple pass-through handler for testing
        return $context;
    }
}
