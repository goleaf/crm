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
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->getLabel()])
            ->all();
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
