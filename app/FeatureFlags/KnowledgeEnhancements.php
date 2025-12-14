<?php

declare(strict_types=1);

namespace App\FeatureFlags;

use Stephenjude\FilamentFeatureFlag\Traits\WithFeatureResolver;

final class KnowledgeEnhancements
{
    use WithFeatureResolver;

    protected bool $defaultValue = false;

    public static function description(): string
    {
        return 'Enables enriched knowledge widgets and template response workflows.';
    }
}