<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CasePriority: string implements HasColor, HasLabel
{
    case P1 = 'p1';
    case P2 = 'p2';
    case P3 = 'p3';
    case P4 = 'p4';

    public function getLabel(): string
    {
        return match ($this) {
            self::P1 => __('enums.case_priority.p1'),
            self::P2 => __('enums.case_priority.p2'),
            self::P3 => __('enums.case_priority.p3'),
            self::P4 => __('enums.case_priority.p4'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::P1 => 'danger',
            self::P2 => 'warning',
            self::P3 => 'info',
            self::P4 => 'gray',
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
