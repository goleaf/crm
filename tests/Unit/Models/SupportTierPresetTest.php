<?php

declare(strict_types=1);

use App\Models\InMemory\SupportTierPreset;

it('exposes static support tier presets with typed data', function (): void {
    $tiers = SupportTierPreset::query()->orderBy('id')->get();

    expect($tiers)->toHaveCount(3)
        ->and($tiers->pluck('slug')->all())->toBe(['standard', 'priority', 'enterprise'])
        ->and($tiers->firstWhere('slug', 'priority')?->response_minutes)->toBe(120)
        ->and($tiers->firstWhere('slug', 'enterprise')?->channels)->toBe(['email', 'chat', 'phone']);
});
