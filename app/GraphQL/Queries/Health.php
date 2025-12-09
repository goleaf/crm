<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

final class Health
{
    /**
     * Simple resolver used for the public health query.
     */
    public function __invoke(): array
    {
        return [
            'name' => config('app.name'),
            'environment' => app()->environment(),
            'frameworkVersion' => app()->version(),
            'phpVersion' => PHP_VERSION,
            'timestamp' => now(),
        ];
    }
}
