<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MilestonePriority: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function getLabel(): string
    {
        return match ($this) {
            self::LOW => __('enums.milestone_priority.low'),
            self::MEDIUM => __('enums.milestone_priority.medium'),
            self::HIGH => __('enums.milestone_priority.high'),
            self::CRITICAL => __('enums.milestone_priority.critical'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::LOW => 'gray',
            self::MEDIUM => 'primary',
            self::HIGH => 'warning',
            self::CRITICAL => 'danger',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function color(): string
    {
        return $this->getColor();
    }
}

