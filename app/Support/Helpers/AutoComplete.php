<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class AutoComplete
{
    /**
     * Build suggestion payloads from a collection.
     *
     * @param  Collection<array-key, mixed>|array<int, mixed>  $items
     * @return array<int, array{value:mixed,label:string}>
     */
    public static function fromCollection(Collection|array $items, string $labelKey = 'name', string $valueKey = 'id', int $limit = 8): array
    {
        return collect($items)
            ->take($limit)
            ->map(fn (mixed $item): array => [
                'value' => data_get($item, $valueKey),
                'label' => (string) data_get($item, $labelKey),
            ])
            ->all();
    }

    /**
     * Build suggestions from an Eloquent query with optional search term.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return array<int, array{value:mixed,label:string}>
     */
    public static function fromQuery(Builder $query, ?string $search = null, string $labelColumn = 'name', string $valueColumn = 'id', int $limit = 8): array
    {
        $builder = clone $query;

        if ($search !== null && $search !== '') {
            $builder->where($labelColumn, 'like', '%'.$search.'%');
        }

        return self::fromCollection($builder->limit($limit)->get([$labelColumn, $valueColumn]), $labelColumn, $valueColumn, $limit);
    }

    /**
     * Wrap suggestions with a placeholder when no matches exist.
     *
     * @param  array<int, array{value:mixed,label:string}>  $suggestions
     * @return array<int, array{value:mixed,label:string}>
     */
    public static function withFallback(array $suggestions, string $label = 'No results found'): array
    {
        if ($suggestions !== []) {
            return $suggestions;
        }

        return [
            [
                'value' => null,
                'label' => $label,
            ],
        ];
    }
}
