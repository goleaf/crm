<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum QuoteDiscountType: string implements HasLabel
{
    case PERCENT = 'percent';
    case FIXED = 'fixed';

    public function getLabel(): string
    {
        return match ($this) {
            self::PERCENT => __('enums.quote_discount_type.percent'),
            self::FIXED => __('enums.quote_discount_type.fixed'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $type) {
            $options[$type->value] = ${$type}->getLabel();
        }

        return $options;
    }

    public function calculate(float $base, float $value): float
    {
        return match ($this) {
            self::PERCENT => round($base * max($value, 0) / 100, 2),
            self::FIXED => round(max($value, 0), 2),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
