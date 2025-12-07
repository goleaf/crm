<?php

declare(strict_types=1);

namespace App\Enums;

enum TerritoryType: string
{
    case GEOGRAPHIC = 'geographic';
    case PRODUCT = 'product';
    case HYBRID = 'hybrid';

    public function getLabel(): string
    {
        return match ($this) {
            self::GEOGRAPHIC => __('enums.territory_type.geographic'),
            self::PRODUCT => __('enums.territory_type.product'),
            self::HYBRID => __('enums.territory_type.hybrid'),
        };
    }
}
