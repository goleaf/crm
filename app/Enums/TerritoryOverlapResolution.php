<?php

declare(strict_types=1);

namespace App\Enums;

enum TerritoryOverlapResolution: string
{
    case SPLIT = 'split';
    case PRIORITY = 'priority';
    case MANUAL = 'manual';

    public function getLabel(): string
    {
        return match ($this) {
            self::SPLIT => __('enums.territory_overlap_resolution.split'),
            self::PRIORITY => __('enums.territory_overlap_resolution.priority'),
            self::MANUAL => __('enums.territory_overlap_resolution.manual'),
        };
    }
}
