<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProductStatus: string implements HasColor, HasLabel
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DISCONTINUED = 'discontinued';
    case DRAFT = 'draft';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => __('enums.product_status.active'),
            self::INACTIVE => __('enums.product_status.inactive'),
            self::DISCONTINUED => __('enums.product_status.discontinued'),
            self::DRAFT => __('enums.product_status.draft'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'warning',
            self::DISCONTINUED => 'danger',
            self::DRAFT => 'gray',
        };
    }

    public function color(): string
    {
        return $this->getColor();
    }

    public function isSellable(): bool
    {
        return $this === self::ACTIVE;
    }

    public function allowsNewSales(): bool
    {
        return $this === self::ACTIVE;
    }
}
