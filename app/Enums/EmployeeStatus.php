<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EmployeeStatus: string implements HasColor, HasLabel
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ON_LEAVE = 'on_leave';
    case TERMINATED = 'terminated';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => __('enums.employee_status.active'),
            self::INACTIVE => __('enums.employee_status.inactive'),
            self::ON_LEAVE => __('enums.employee_status.on_leave'),
            self::TERMINATED => __('enums.employee_status.terminated'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'gray',
            self::ON_LEAVE => 'warning',
            self::TERMINATED => 'danger',
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
