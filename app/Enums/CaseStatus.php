<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CaseStatus: string implements HasColor, HasLabel
{
    case NEW = 'new';
    case ASSIGNED = 'assigned';
    case PENDING_INPUT = 'pending_input';
    case CLOSED = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::NEW => __('enums.case_status.new'),
            self::ASSIGNED => __('enums.case_status.assigned'),
            self::PENDING_INPUT => __('enums.case_status.pending_input'),
            self::CLOSED => __('enums.case_status.closed'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NEW => 'info',
            self::ASSIGNED => 'primary',
            self::PENDING_INPUT => 'warning',
            self::CLOSED => 'success',
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
