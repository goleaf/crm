<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProductAttributeDataType: string implements HasColor, HasLabel
{
    case TEXT = 'text';
    case NUMBER = 'number';
    case SELECT = 'select';
    case MULTI_SELECT = 'multi_select';
    case BOOLEAN = 'boolean';

    public function getLabel(): string
    {
        return match ($this) {
            self::TEXT => __('enums.product_attribute_data_type.text'),
            self::NUMBER => __('enums.product_attribute_data_type.number'),
            self::SELECT => __('enums.product_attribute_data_type.select'),
            self::MULTI_SELECT => __('enums.product_attribute_data_type.multi_select'),
            self::BOOLEAN => __('enums.product_attribute_data_type.boolean'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::TEXT => 'gray',
            self::NUMBER => 'blue',
            self::SELECT => 'green',
            self::MULTI_SELECT => 'purple',
            self::BOOLEAN => 'orange',
        };
    }

    public function color(): string
    {
        return $this->getColor();
    }

    /**
     * Check if this data type requires predefined values
     */
    public function requiresValues(): bool
    {
        return match ($this) {
            self::SELECT, self::MULTI_SELECT => true,
            default => false,
        };
    }

    /**
     * Validate a value against this data type
     */
    public function validateValue(mixed $value): bool
    {
        return match ($this) {
            self::TEXT => is_string($value),
            self::NUMBER => is_numeric($value),
            self::SELECT => is_string($value),
            self::MULTI_SELECT => is_array($value) && collect($value)->every(fn ($v): bool => is_string($v)),
            self::BOOLEAN => is_bool($value) || in_array($value, ['0', '1', 'true', 'false'], true),
        };
    }

    /**
     * Cast a value to the appropriate type for this data type
     */
    public function castValue(mixed $value): mixed
    {
        return match ($this) {
            self::TEXT => (string) $value,
            self::NUMBER => is_numeric($value) ? (float) $value : $value,
            self::SELECT => (string) $value,
            self::MULTI_SELECT => is_array($value) ? $value : [$value],
            self::BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN),
        };
    }
}
