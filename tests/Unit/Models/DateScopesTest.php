<?php

declare(strict_types=1);

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('filters records using date scopes on created_at', function (): void {
    \Illuminate\Support\Facades\Date::setTestNow('2025-01-15 12:00:00');

    $recent = Lead::factory()->create([
        'created_at' => now()->subDays(3),
    ]);

    $boundary = Lead::factory()->create([
        'created_at' => now()->subDays(7)->startOfDay(),
    ]);

    $today = Lead::factory()->create([
        'created_at' => now(),
    ]);

    Lead::factory()->create([
        'created_at' => now()->subDays(8),
    ]);

    expect(Lead::query()->ofLast7Days()->pluck('id'))
        ->toContain($recent->getKey(), $boundary->getKey())
        ->not->toContain($today->getKey());

    expect(Lead::query()->ofToday()->pluck('id'))
        ->toContain($today->getKey())
        ->not->toContain($boundary->getKey());

    \Illuminate\Support\Facades\Date::setTestNow();
});

it('applies date scopes to alternate timestamp columns', function (): void {
    \Illuminate\Support\Facades\Date::setTestNow('2025-01-15 12:00:00');

    Lead::factory()->create([
        'created_at' => now()->subDays(20),
        'updated_at' => now()->subDays(2),
    ]);

    Lead::factory()->create([
        'created_at' => now()->subDays(1),
        'updated_at' => now()->subDays(10),
    ]);

    Lead::factory()->create([
        'created_at' => now()->subDays(1),
        'updated_at' => now()->subDays(9),
    ]);

    expect(Lead::query()->ofLast7Days(column: 'updated_at')->count())->toBe(1);

    \Illuminate\Support\Facades\Date::setTestNow();
});
