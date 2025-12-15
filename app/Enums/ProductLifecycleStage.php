<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProductLifecycleStage: string implements HasColor, HasLabel
{
    case CONCEPT = 'concept';
    case DEVELOPMENT = 'development';
    case TESTING = 'testing';
    case RELEASED = 'released';
    case ACTIVE = 'active';
    case MATURE = 'mature';
    case DECLINING = 'declining';
    case DISCONTINUED = 'discontinued';
    case END_OF_LIFE = 'end_of_life';

    public function getLabel(): string
    {
        return match ($this) {
            self::CONCEPT => __('enums.product_lifecycle_stage.concept'),
            self::DEVELOPMENT => __('enums.product_lifecycle_stage.development'),
            self::TESTING => __('enums.product_lifecycle_stage.testing'),
            self::RELEASED => __('enums.product_lifecycle_stage.released'),
            self::ACTIVE => __('enums.product_lifecycle_stage.active'),
            self::MATURE => __('enums.product_lifecycle_stage.mature'),
            self::DECLINING => __('enums.product_lifecycle_stage.declining'),
            self::DISCONTINUED => __('enums.product_lifecycle_stage.discontinued'),
            self::END_OF_LIFE => __('enums.product_lifecycle_stage.end_of_life'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::CONCEPT => 'gray',
            self::DEVELOPMENT => 'info',
            self::TESTING => 'warning',
            self::RELEASED => 'success',
            self::ACTIVE => 'success',
            self::MATURE => 'primary',
            self::DECLINING => 'warning',
            self::DISCONTINUED => 'danger',
            self::END_OF_LIFE => 'danger',
        };
    }

    public function color(): string
    {
        return $this->getColor();
    }

    public function isSellable(): bool
    {
        return in_array($this, [self::RELEASED, self::ACTIVE, self::MATURE], true);
    }

    public function allowsNewSales(): bool
    {
        return in_array($this, [self::RELEASED, self::ACTIVE, self::MATURE], true);
    }
}
