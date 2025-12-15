<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CalendarEventType: string implements HasColor, HasLabel
{
    case MEETING = 'meeting';
    case CALL = 'call';
    case LUNCH = 'lunch';
    case DEMO = 'demo';
    case FOLLOW_UP = 'follow_up';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::MEETING => __('enums.calendar_event_type.meeting'),
            self::CALL => __('enums.calendar_event_type.call'),
            self::LUNCH => __('enums.calendar_event_type.lunch'),
            self::DEMO => __('enums.calendar_event_type.demo'),
            self::FOLLOW_UP => __('enums.calendar_event_type.follow_up'),
            self::OTHER => __('enums.calendar_event_type.other'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MEETING => '#3b82f6',
            self::CALL => '#10b981',
            self::LUNCH => '#f59e0b',
            self::DEMO => '#f59e0b',
            self::FOLLOW_UP => '#8b5cf6',
            self::OTHER => '#6b7280',
        };
    }

    public function color(): string
    {
        return $this->getColor();
    }
}
