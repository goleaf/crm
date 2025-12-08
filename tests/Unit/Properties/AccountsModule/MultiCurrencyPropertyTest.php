<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Team;
use App\Models\User;

/**
 * **Feature: accounts-module, Property 32: Currency code persistence**
 *
 * **Validates: Requirements 13.1, 13.2**
 *
 * Properties:
 * - For any account with a designated currency code, the currency should be persisted,
 *   retrievable, and used for displaying financial data in the correct currency.
 */

// Property 32: Currency code persistence on account creation
test('property: currency code is persisted when creating an account', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    // Get available currencies from config
    $currencies = array_keys(config('company.currency_codes', ['USD' => 'USD']));
    $selectedCurrency = fake()->randomElement($currencies);

    $company = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
            'currency_code' => $selectedCurrency,
        ]);

    // Verify currency is persisted
    expect($company->currency_code)->toBe($selectedCurrency);

    // Verify currency is retrievable after fresh query
    $retrieved = Company::find($company->getKey());
    expect($retrieved->currency_code)->toBe($selectedCurrency);
})->repeat(100);

// Property 32: Currency code defaults to USD when not specified
test('property: currency code defaults to USD when not explicitly set', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    // Create company without specifying currency_code
    $company = Company::create([
        'team_id' => $team->getKey(),
        'account_owner_id' => $owner->getKey(),
        'name' => fake()->company(),
    ]);

    // Verify default currency is USD
    $defaultCurrency = config('company.default_currency', 'USD');
    expect($company->currency_code)->toBe($defaultCurrency);

    // Verify after fresh query
    $retrieved = Company::find($company->getKey());
    expect($retrieved->currency_code)->toBe($defaultCurrency);
})->repeat(50);

// Property 32: Currency code can be updated
test('property: currency code can be updated on existing account', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $currencies = array_keys(config('company.currency_codes', ['USD' => 'USD']));
    $initialCurrency = fake()->randomElement($currencies);

    $company = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
            'currency_code' => $initialCurrency,
        ]);

    // Select a different currency
    $newCurrency = fake()->randomElement(
        array_filter($currencies, fn ($c) => $c !== $initialCurrency)
    );

    // If all currencies are the same, skip this iteration
    if ($newCurrency === null) {
        $newCurrency = $initialCurrency;
    }

    $company->update(['currency_code' => $newCurrency]);

    // Verify update persisted
    expect($company->currency_code)->toBe($newCurrency);

    // Verify after fresh query
    $retrieved = Company::find($company->getKey());
    expect($retrieved->currency_code)->toBe($newCurrency);
})->repeat(100);

// Property 32: Currency code is filterable across accounts
test('property: accounts can be filtered by currency code', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $currencies = array_keys(config('company.currency_codes', ['USD' => 'USD']));

    // Create companies with different currencies
    $companiesByCurrency = [];
    foreach ($currencies as $currency) {
        $count = fake()->numberBetween(1, 3);
        for ($i = 0; $i < $count; $i++) {
            $company = Company::factory()
                ->for($team)
                ->create([
                    'account_owner_id' => $owner->getKey(),
                    'currency_code' => $currency,
                ]);
            $companiesByCurrency[$currency][] = $company->getKey();
        }
    }

    // Filter by each currency and verify results
    foreach ($currencies as $currency) {
        $filtered = Company::where('team_id', $team->getKey())
            ->where('currency_code', $currency)
            ->pluck('id')
            ->toArray();

        $expected = $companiesByCurrency[$currency] ?? [];

        // All expected companies should be in filtered results
        foreach ($expected as $expectedId) {
            expect($filtered)->toContain($expectedId);
        }

        // All filtered results should have the correct currency
        $filteredCompanies = Company::whereIn('id', $filtered)->get();
        foreach ($filteredCompanies as $filteredCompany) {
            expect($filteredCompany->currency_code)->toBe($currency);
        }
    }
})->repeat(25);

// Property 32: Currency code is preserved through model refresh
test('property: currency code is preserved through model operations', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $currencies = array_keys(config('company.currency_codes', ['USD' => 'USD']));
    $selectedCurrency = fake()->randomElement($currencies);

    $company = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
            'currency_code' => $selectedCurrency,
        ]);

    // Update other fields
    $company->update([
        'name' => fake()->company(),
        'website' => fake()->url(),
        'employee_count' => fake()->numberBetween(10, 1000),
    ]);

    // Currency should remain unchanged
    expect($company->currency_code)->toBe($selectedCurrency);

    // Refresh and verify
    $company->refresh();
    expect($company->currency_code)->toBe($selectedCurrency);

    // Fresh query and verify
    $retrieved = Company::find($company->getKey());
    expect($retrieved->currency_code)->toBe($selectedCurrency);
})->repeat(100);

// Property 32: Currency code is included in model array/JSON representation
test('property: currency code is included in model serialization', function (): void {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->users()->attach($owner);

    $currencies = array_keys(config('company.currency_codes', ['USD' => 'USD']));
    $selectedCurrency = fake()->randomElement($currencies);

    $company = Company::factory()
        ->for($team)
        ->create([
            'account_owner_id' => $owner->getKey(),
            'currency_code' => $selectedCurrency,
        ]);

    // Verify currency is in toArray output
    $array = $company->toArray();
    expect($array)->toHaveKey('currency_code')
        ->and($array['currency_code'])->toBe($selectedCurrency);
})->repeat(50);

