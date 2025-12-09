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
            $value,
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
     * @param array<int|string, mixed> $items
     *
     * @return array<int|string, mixed>
     */
    public static function keyBy(array $items, string $key): array
    {
        return Arr::keyBy($items, $key);
    }

    /**
     * Pluck values from an array using dot notation.
     *
     * @param array<int|string, mixed>  $items
     * @param string|array<int, string> $value
     *
     * @return array<int, mixed>
     */
    public static function pluck(array $items, string|array $value, ?string $key = null): array
    {
        return Arr::pluck($items, $value, $key);
    }

    /**
     * Get the first matching item from an array.
     *
     * @param array<int|string, mixed> $items
     */
    public static function first(array $items, ?callable $callback = null, mixed $default = null): mixed
    {
        return Arr::first($items, $callback, $default);
    }

    /**
     * Get the last matching item from an array.
     *
     * @param array<int|string, mixed> $items
     */
    public static function last(array $items, ?callable $callback = null, mixed $default = null): mixed
    {
        return Arr::last($items, $callback, $default);
    }

    /**
     * Retrieve a value from a nested array using dot notation.
     *
     * @param array<int|string, mixed> $items
     */
    public static function get(array $items, string|int|null $key, mixed $default = null): mixed
    {
        return Arr::get($items, $key, $default);
    }

    /**
     * Set a value in a nested array using dot notation.
     *
     * @param array<int|string, mixed> $items
     *
     * @return array<int|string, mixed>
     */
    public static function set(array &$items, string|int|null $key, mixed $value): array
    {
        return Arr::set($items, $key, $value);
    }

    /**
     * Remove items from an array using dot notation.
     *
     * @param array<int|string, mixed>  $items
     * @param string|array<int, string> $keys
     */
    public static function forget(array &$items, string|array $keys): void
    {
        Arr::forget($items, $keys);
    }

    /**
     * Check if an item exists in an array using dot notation.
     *
     * @param array<int|string, mixed>  $items
     * @param string|array<int, string> $keys
     */
    public static function has(array $items, string|array $keys): bool
    {
        return Arr::has($items, $keys);
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param array<int|string, mixed> $items
     *
     * @return array<int, mixed>
     */
    public static function flatten(array $items, int $depth = INF): array
    {
        return Arr::flatten($items, $depth);
    }

    /**
     * Filter an array using a callback.
     *
     * @param array<int|string, mixed> $items
     *
     * @return array<int|string, mixed>
     */
    public static function where(array $items, callable $callback): array
    {
        return Arr::where($items, $callback);
    }

    /**
     * Get a subset of items from an array.
     *
     * @param array<int|string, mixed> $items
     * @param array<int, string>       $keys
     *
     * @return array<int|string, mixed>
     */
    public static function only(array $items, array $keys): array
    {
        return Arr::only($items, $keys);
    }

    /**
     * Get all items except specified keys.
     *
     * @param array<int|string, mixed> $items
     * @param array<int, string>       $keys
     *
     * @return array<int|string, mixed>
     */
    public static function except(array $items, array $keys): array
    {
        return Arr::except($items, $keys);
    }

    /**
     * Divide an array into keys and values.
     *
     * @param array<int|string, mixed> $items
     *
     * @return array{0: array<int, int|string>, 1: array<int, mixed>}
     */
    public static function divide(array $items): array
    {
        return Arr::divide($items);
    }

    /**
     * Shuffle an array randomly.
     *
     * @param array<int|string, mixed> $items
     *
     * @return array<int|string, mixed>
     */
    public static function shuffle(array $items): array
    {
        return Arr::shuffle($items);
    }

    /**
     * Sort array by multiple fields.
     *
     * @param array<int|string, mixed> $items
     * @param array<string, string>    $sortBy ['field' => 'asc|desc']
     *
     * @return array<int|string, mixed>
     */
    public static function sortByMultiple(array $items, array $sortBy): array
    {
        return Arr::sort($items, function ($item) use ($sortBy): array {
            $result = [];
            foreach ($sortBy as $field => $direction) {
                $value = data_get($item, $field);
                $result[] = $direction === 'desc' ? -$value : $value;
            }

            return $result;
        });
    }

    /**
     * Group array items by a key.
     *
     * @param array<int|string, mixed> $items
     *
     * @return array<int|string, array<int, mixed>>
     */
    public static function groupBy(array $items, string|callable $groupBy): array
    {
        $results = [];

        foreach ($items as $key => $value) {
            $groupKey = is_callable($groupBy) ? $groupBy($value, $key) : data_get($value, $groupBy);

            if (! isset($results[$groupKey])) {
                $results[$groupKey] = [];
            }

            $results[$groupKey][] = $value;
        }

        return $results;
    }

    /**
     * Check if array is associative.
     *
     * @param array<int|string, mixed> $items
     */
    public static function isAssoc(array $items): bool
    {
        return Arr::isAssoc($items);
    }

    /**
     * Wrap value in array if not already an array.
     *
     * @return array<int|string, mixed>
     */
    public static function wrap(mixed $value): array
    {
        return Arr::wrap($value);
    }
}
