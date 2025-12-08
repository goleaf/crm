<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

final class ArrayHelper
{
    /**
     * Join a mixed list (array, collection, JSON string) into a readable string.
     */
    public static function joinList(
        mixed $value,
        string $separator = ', ',
        ?string $finalSeparator = null,
        ?string $emptyPlaceholder = '—',
        bool $trimStrings = true,
    ): ?string {
        if ($value instanceof Collection) {
            $value = $value->all();
        }

        if ($value === null || $value === '') {
            return $emptyPlaceholder;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : [$value];
        }

        if (! is_array($value)) {
            $stringValue = is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))
                ? (string) $value
                : null;

            return filled($stringValue) ? $stringValue : $emptyPlaceholder;
        }

        $normalized = array_values(array_filter(array_map(
            static function (mixed $item) use ($trimStrings): mixed {
                if ($trimStrings && is_string($item)) {
                    return trim($item);
                }

                return $item;
            },
            $value
        ), static fn (mixed $item): bool => ! in_array($item, [null, ''], true)));

        if ($normalized === []) {
            return $emptyPlaceholder;
        }

        return $finalSeparator !== null
            ? Arr::join($normalized, $separator, $finalSeparator)
            : Arr::join($normalized, $separator);
    }

    /**
     * Key an array by a specific attribute.
     *
     * @param  array<int|string, mixed>  $items
     * @return array<int|string, mixed>
     */
    public static function keyBy(array $items, string $key): array
    {
        return Arr::keyBy($items, $key);
    }

    /**
     * Pluck values from an array using dot notation.
     *
     * @param  array<int|string, mixed>  $items
     * @param  string|array<int, string>  $value
     * @return array<int, mixed>
     */
    public static function pluck(array $items, string|array $value, ?string $key = null): array
    {
        return Arr::pluck($items, $value, $key);
    }

    /**
     * Get the first matching item from an array.
     *
     * @param  array<int|string, mixed>  $items
     */
    public static function first(array $items, ?callable $callback = null, mixed $default = null): mixed
    {
        return Arr::first($items, $callback, $default);
    }

    /**
     * Get the last matching item from an array.
     *
     * @param  array<int|string, mixed>  $items
     */
    public static function last(array $items, ?callable $callback = null, mixed $default = null): mixed
    {
        return Arr::last($items, $callback, $default);
    }

    /**
     * Retrieve a value from a nested array using dot notation.
     *
     * @param  array<int|string, mixed>  $items
     */
    public static function get(array $items, string|int|null $key, mixed $default = null): mixed
    {
        return Arr::get($items, $key, $default);
    }
}
