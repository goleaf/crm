<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use UnitEnum;

/**
 * Cast for enum attributes with enhanced error handling.
 *
 * Usage in model:
 * protected $casts = [
 *     'status' => EnumCast::class.':'.ProjectStatus::class,
 * ];
 *
 * @template TEnum of UnitEnum
 */
final readonly class EnumCast implements CastsAttributes
{
    /**
     * @param  class-string<TEnum>  $enumClass
     */
    public function __construct(
        private string $enumClass
    ) {
        if (! enum_exists($enumClass)) {
            throw new InvalidArgumentException("Class {$enumClass} is not an enum.");
        }
    }

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     * @return TEnum|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?UnitEnum
    {
        if ($value === null) {
            return null;
        }

        $enum = $this->enumClass;

        if (method_exists($enum, 'tryFrom')) {
            return $enum::tryFrom($value);
        }

        // For unit enums
        foreach ($enum::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof UnitEnum) {
            // For backed enums, use the value
            if (property_exists($value, 'value')) {
                return (string) $value->value;
            }

            // For unit enums, use the name
            return $value->name;
        }

        // If it's already a string, validate and return
        if (is_string($value)) {
            $enum = $this->enumClass;

            if (method_exists($enum, 'tryFrom') && $enum::tryFrom($value) !== null) {
                return $value;
            }
        }

        throw new InvalidArgumentException(
            "Value must be an instance of {$this->enumClass} or a valid enum value."
        );
    }
}
