<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LeaveAccrualFrequency: string implements HasLabel
{
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case ANNUALLY = 'annually';

    public function getLabel(): string
    {
        return match ($this) {
            self::MONTHLY => __('enums.leave_accrual_frequency.monthly'),
            self::QUARTERLY => __('enums.leave_accrual_frequency.quarterly'),
            self::ANNUALLY => __('enums.leave_accrual_frequency.annually'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
