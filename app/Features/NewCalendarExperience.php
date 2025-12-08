<?php

declare(strict_types=1);

namespace App\Features;

use Stephenjude\FilamentFeatureFlag\Traits\WithFeatureResolver;

final class NewCalendarExperience
{
    use WithFeatureResolver;

    protected bool $defaultValue = false;

    public static function description(): string
    {
        return 'Enables the optimized calendar views, recurrence, and performance tweaks.';
    }
}
