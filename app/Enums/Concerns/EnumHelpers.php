<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

use BenSampo\Enum\Enum;

/**
 * Enhanced enum helpers trait that bridges native PHP enums with BenSampo package features.
 *
 * This trait provides additional functionality for native PHP 8.1+ enums:
 * - Validation rules
 * - Array conversion helpers
 * - Value/key lookups
 * - Random instance generation
 */
trait EnumHelpers
{
    /**
     * Get all enum values as an array.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all enum names as an array.
     *
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * Get enum instance from value with null fallback.
     */
    public static function fromValueOrNull(string|int|null $value): ?self
    {
        if ($value === null) {
            return null;
        }

        return self::tryFrom($value);
    }

    /**
     * Get enum instance from name.
     */
    public static function fromName(string $name): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Get a random enum instance.
     */
    public static function random(): self
    {
        $cases = self::cases();

        return $cases[array_rand($cases)];
    }

    /**
     * Check if a value is valid for this enum.
     */
    public static function isValid(mixed $value): bool
    {
        return self::tryFrom($value) !== null;
    }

    /**
     * Get validation rule for this enum.
     */
    public static function rule(): string
    {
        return 'in:'.implode(',', self::values());
    }

    /**
     * Get Laravel validation rule as array.
     *
     * @return array<string>
     */
    public static function rules(): array
    {
        return ['in:'.implode(',', self::values())];
    }

    /**
     * Get all enum instances as a collection.
     *
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function collect(): \Illuminate\Support\Collection
    {
        return collect(self::cases());
    }

    /**
     * Get enum as associative array [value => label].
     *
     * @return array<string, string>
     */
    public static function toSelectArray(): array
    {
        return self::collect()
            ->mapWithKeys(fn (self $case): array => [
                $case->value => method_exists($case, 'getLabel')
                    ? $case->getLabel()
                    : $case->name,
            ])
            ->toArray();
    }

    /**
     * Get enum as array of objects with value and label.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function toArray(): array
    {
        return self::collect()
            ->map(fn (self $case): array => [
                'value' => $case->value,
                'label' => method_exists($case, 'getLabel')
                    ? $case->getLabel()
                    : $case->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Check if enum has a specific value.
     */
    public static function hasValue(string|int $value): bool
    {
        return in_array($value, self::values(), true);
    }

    /**
     * Check if enum has a specific name.
     */
    public static function hasName(string $name): bool
    {
        return in_array($name, self::names(), true);
    }

    /**
     * Get count of enum cases.
     */
    public static function count(): int
    {
        return count(self::cases());
    }
}
