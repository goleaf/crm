<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VendorStatus: string implements HasColor, HasLabel
{
    case ACTIVE = 'active';
    case ON_HOLD = 'on_hold';
    case INACTIVE = 'inactive';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => __('enums.vendor_status.active'),
            self::ON_HOLD => __('enums.vendor_status.on_hold'),
            self::INACTIVE => __('enums.vendor_status.inactive'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::ON_HOLD => 'warning',
            self::INACTIVE => 'gray',
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
