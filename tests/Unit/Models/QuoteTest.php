<?php

declare(strict_types=1);

use App\Enums\QuoteDiscountType;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('quote calculates totals with discounts and taxes', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    actingAs($user);
    $user->switchTeam($team);

    $quote = Quote::create([
        'team_id' => $team->getKey(),
        'creator_id' => $user->getKey(),
        'owner_id' => $user->getKey(),
        'title' => 'Test Quote',
        'status' => QuoteStatus::DRAFT,
        'currency_code' => 'USD',
    ]);

    $quote->lineItems()->create([
        'name' => 'Widget Pro',
        'team_id' => $team->getKey(),
        'quantity' => 2,
        'unit_price' => 100,
        'discount_type' => QuoteDiscountType::PERCENT,
        'discount_value' => 10,
        'tax_rate' => 8,
    ]);

    $quote->lineItems()->create([
        'name' => 'Implementation Support',
        'team_id' => $team->getKey(),
        'quantity' => 1,
        'unit_price' => 50,
        'discount_type' => QuoteDiscountType::FIXED,
        'discount_value' => 5,
        'tax_rate' => 0,
    ]);

    $quote->syncFinancials();
    $quote->refresh();

    expect((float) $quote->subtotal)->toBe(225.00)
        ->and((float) $quote->discount_total)->toBe(25.00)
        ->and((float) $quote->tax_total)->toBe(14.40)
        ->and((float) $quote->total)->toBe(239.40)
        ->and($quote->line_items)->toHaveCount(2)
        ->and($quote->owner_id)->toBe($user->getKey());
});