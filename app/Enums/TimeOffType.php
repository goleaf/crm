<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TimeOffType: string implements HasLabel
{
    case VACATION = 'vacation';
    case SICK = 'sick';
    case PERSONAL = 'personal';
    case BEREAVEMENT = 'bereavement';
    case PARENTAL = 'parental';
    case UNPAID = 'unpaid';

    public function getLabel(): string
    {
        return match ($this) {
            self::VACATION => __('enums.time_off_type.vacation'),
            self::SICK => __('enums.time_off_type.sick'),
            self::PERSONAL => __('enums.time_off_type.personal'),
            self::BEREAVEMENT => __('enums.time_off_type.bereavement'),
            self::PARENTAL => __('enums.time_off_type.parental'),
            self::UNPAID => __('enums.time_off_type.unpaid'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
