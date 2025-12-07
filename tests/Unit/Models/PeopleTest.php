<?php

declare(strict_types=1);

use App\Models\People;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('people supports contact hierarchy and contact field casts', function () {
    $manager = People::factory()->create();

    $report = People::factory()->create([
        'reports_to_id' => $manager->getKey(),
        'segments' => ['VIP', 'Customer'],
        'social_links' => ['linkedin' => 'https://example.com'],
        'birthdate' => '1990-01-01',
        'is_portal_user' => true,
        'sync_enabled' => true,
    ]);

    expect($report->reportsTo?->is($manager))->toBeTrue()
        ->and($manager->reports->first()?->is($report))->toBeTrue()
        ->and($report->segments)->toBe(['VIP', 'Customer'])
        ->and($report->birthdate)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($report->is_portal_user)->toBeTrue()
        ->and($report->sync_enabled)->toBeTrue()
        ->and($report->social_links)->toBe(['linkedin' => 'https://example.com']);
});
