<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use BackedEnum;
use Illuminate\Support\Collection;

final class SelectOptions
{
    /**
     * Add a placeholder as the first option.
     *
     * @param array<int|string, string> $options
     *
     * @return array<int|string, string>
     */
    public static function withPlaceholder(array $options, string $label = 'Select...'): array
    {
        return ['' => $label] + $options;
    }

    /**
     * Build options from a backed enum. Uses getLabel() when available.
     *
     * @param class-string<BackedEnum> $enum
     *
     * @return array<int|string, string>
     */
    public static function fromEnum(string $enum): array
    {
        return collect($enum::cases())
            ->mapWithKeys(fn (BackedEnum $case): array => [
                $case->value => method_exists($case, 'getLabel') ? $case->getLabel() : $case->name,
            ])
            ->all();
    }

    /**
     * Build options from a collection or array of items.
     *
     * @param Collection<array-key, mixed>|array<int, mixed> $items
     *
     * @return array<int|string, string>
     */
    public static function fromCollection(Collection|array $items, string $valueKey = 'id', callable|string $label = 'name'): array
    {
        return collect($items)
            ->mapWithKeys(function (mixed $item) use ($valueKey, $label): array {
                $value = data_get($item, $valueKey);
                $text = is_callable($label) ? $label($item) : data_get($item, $label);

                return $value === null ? [] : [$value => (string) $text];
            })
            ->filter(fn (mixed $text, mixed $key): bool => filled($key) && filled($text))
            ->all();
    }

    /**
     * Transform a simple list into value => label pairs, keeping order.
     *
     * @param array<int|string, string> $values
     *
     * @return array<int|string, string>
     */
    public static function fromArray(array $values): array
    {
        return collect($values)
            ->mapWithKeys(fn (string $label, int|string $key): array => [$key => $label])
            ->all();
    }
}
