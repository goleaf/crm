<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CalendarEventStatus: string implements HasColor, HasLabel
{
    case SCHEDULED = 'scheduled';
    case CONFIRMED = 'confirmed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::SCHEDULED => __('enums.calendar_event_status.scheduled'),
            self::CONFIRMED => __('enums.calendar_event_status.confirmed'),
            self::COMPLETED => __('enums.calendar_event_status.completed'),
            self::CANCELLED => __('enums.calendar_event_status.cancelled'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SCHEDULED => 'info',
            self::CONFIRMED => 'primary',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
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
