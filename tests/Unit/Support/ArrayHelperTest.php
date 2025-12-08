<?php

declare(strict_types=1);

use App\Support\Helpers\ArrayHelper;
use Illuminate\Support\Collection;

it('joins lists from arrays, collections, and json strings', function (): void {
    expect(ArrayHelper::joinList(['Tailwind', 'Laravel']))->toBe('Tailwind, Laravel')
        ->and(ArrayHelper::joinList(new Collection(['API', 'CRM']), ', ', ' & '))->toBe('API & CRM')
        ->and(ArrayHelper::joinList('["one","two"]'))->toBe('one, two')
        ->and(ArrayHelper::joinList([], emptyPlaceholder: null))->toBeNull();
});

it('returns scalar fallbacks when list is not an array', function (): void {
    expect(ArrayHelper::joinList('value'))->toBe('value')
        ->and(ArrayHelper::joinList(null, emptyPlaceholder: '—'))->toBe('—');
});

it('keys arrays by the provided attribute', function (): void {
    $items = [
        ['id' => 'prod-100', 'name' => 'Desk'],
        ['id' => 'prod-200', 'name' => 'Chair'],
    ];

    expect(ArrayHelper::keyBy($items, 'id'))->toHaveKey('prod-100')
        ->and(ArrayHelper::keyBy($items, 'id')['prod-200']['name'])->toBe('Chair');
});

it('plucks and retrieves nested values safely', function (): void {
    $items = [
        ['user' => ['email' => 'one@example.com']],
        ['user' => ['email' => 'two@example.com']],
    ];

    expect(ArrayHelper::pluck($items, 'user.email'))->toBe(['one@example.com', 'two@example.com'])
        ->and(ArrayHelper::get($items[0], 'user.email'))->toBe('one@example.com');
});

it('finds the first and last matching items', function (): void {
    $numbers = [100, 200, 300, 110];

    expect(ArrayHelper::first($numbers, fn (int $value): bool => $value > 110))->toBe(200)
        ->and(ArrayHelper::last($numbers, fn (int $value): bool => $value > 110))->toBe(300)
        ->and(ArrayHelper::last([], null, 100))->toBe(100);
});
