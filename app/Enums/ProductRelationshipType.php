<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProductRelationshipType: string implements HasLabel
{
    case BUNDLE = 'bundle';
    case CROSS_SELL = 'cross_sell';
    case UPSELL = 'upsell';
    case DEPENDENCY = 'dependency';
    case ALTERNATIVE = 'alternative';
    case ACCESSORY = 'accessory';

    public function getLabel(): string
    {
        return match ($this) {
            self::BUNDLE => __('enums.product_relationship_type.bundle'),
            self::CROSS_SELL => __('enums.product_relationship_type.cross_sell'),
            self::UPSELL => __('enums.product_relationship_type.upsell'),
            self::DEPENDENCY => __('enums.product_relationship_type.dependency'),
            self::ALTERNATIVE => __('enums.product_relationship_type.alternative'),
            self::ACCESSORY => __('enums.product_relationship_type.accessory'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::BUNDLE => __('enums.product_relationship_type.bundle_description'),
            self::CROSS_SELL => __('enums.product_relationship_type.cross_sell_description'),
            self::UPSELL => __('enums.product_relationship_type.upsell_description'),
            self::DEPENDENCY => __('enums.product_relationship_type.dependency_description'),
            self::ALTERNATIVE => __('enums.product_relationship_type.alternative_description'),
            self::ACCESSORY => __('enums.product_relationship_type.accessory_description'),
        };
    }
}
