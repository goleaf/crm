<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CalendarEventType: string implements HasLabel
{
    case MEETING = 'meeting';
    case CALL = 'call';
    case DEMO = 'demo';
    case FOLLOW_UP = 'follow_up';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::MEETING => __('enums.calendar_event_type.meeting'),
            self::CALL => __('enums.calendar_event_type.call'),
            self::DEMO => __('enums.calendar_event_type.demo'),
            self::FOLLOW_UP => __('enums.calendar_event_type.follow_up'),
            self::OTHER => __('enums.calendar_event_type.other'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
