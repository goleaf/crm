<?php

declare(strict_types=1);

use App\Enums\AccountType;
use App\Enums\AddressType;
use App\Enums\Industry;
use App\Models\Account;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('account factory creates valid account with all required fields', function (): void {
    $account = Account::factory()->create();

    expect($account)->toBeInstanceOf(Account::class)
        ->and($account->name)->not->toBeNull()
        ->and($account->slug)->not->toBeNull()
        ->and($account->team_id)->not->toBeNull()
        ->and($account->owner_id)->not->toBeNull()
        ->and($account->type)->toBeInstanceOf(AccountType::class)
        ->and($account->industry)->toBeInstanceOf(Industry::class);
});

test('account factory generates valid billing address with postal code', function (): void {
    $account = Account::factory()->create();

    expect($account->billing_address)->toBeArray()
        ->and($account->billing_address)->toHaveKeys(['street', 'city', 'state', 'postal_code', 'country'])
        ->and($account->billing_address['postal_code'])->not->toBeNull()
        ->and($account->billing_address['postal_code'])->toBeString();
});

test('account factory generates valid shipping address with postal code', function (): void {
    $account = Account::factory()->create();

    expect($account->shipping_address)->toBeArray()
        ->and($account->shipping_address)->toHaveKeys(['street', 'city', 'state', 'postal_code', 'country'])
        ->and($account->shipping_address['postal_code'])->not->toBeNull()
        ->and($account->shipping_address['postal_code'])->toBeString();
});

test('account factory generates valid addresses array with both billing and shipping', function (): void {
    $account = Account::factory()->create();

    expect($account->addresses)->toBeArray()
        ->and($account->addresses)->toHaveCount(2);

    $billingAddress = collect($account->addresses)->firstWhere('type', AddressType::BILLING->value);
    $shippingAddress = collect($account->addresses)->firstWhere('type', AddressType::SHIPPING->value);

    expect($billingAddress)->not->toBeNull()
        ->and($billingAddress['postal_code'])->not->toBeNull()
        ->and($billingAddress['postal_code'])->toBeString()
        ->and($shippingAddress)->not->toBeNull()
        ->and($shippingAddress['postal_code'])->not->toBeNull()
        ->and($shippingAddress['postal_code'])->toBeString();
});

test('account factory generates valid social links', function (): void {
    $account = Account::factory()->create();

    expect($account->social_links)->toBeArray()
        ->and($account->social_links)->toHaveKeys(['twitter', 'facebook', 'linkedin']);
});

test('account factory generates valid currency from config', function (): void {
    $account = Account::factory()->create();
    $validCurrencies = array_keys(config('company.currency_codes', []));

    if ($validCurrencies !== []) {
        expect($validCurrencies)->toContain($account->currency);
    } else {
        expect($account->currency)->toBe(config('company.default_currency', 'USD'));
    }
});

test('account factory creates associated team and owner', function (): void {
    $account = Account::factory()->create();

    expect($account->team)->toBeInstanceOf(Team::class)
        ->and($account->owner)->toBeInstanceOf(User::class);
});

test('account factory owner is attached to team after creation', function (): void {
    $account = Account::factory()->create();

    expect($account->owner->teams->pluck('id'))->toContain($account->team_id);
});

test('account factory can override default values', function (): void {
    $customName = 'Custom Company Name';
    $customType = AccountType::PARTNER;

    $account = Account::factory()->create([
        'name' => $customName,
        'type' => $customType->value,
    ]);

    expect($account->name)->toBe($customName)
        ->and($account->type)->toBe($customType);
});

test('account factory generates unique slugs for same name', function (): void {
    $accounts = Account::factory()->count(3)->create([
        'name' => 'Same Company Name',
    ]);

    $slugs = $accounts->pluck('slug')->unique();

    expect($slugs)->toHaveCount(3);
});

test('account factory generates valid annual revenue', function (): void {
    $account = Account::factory()->create();

    expect($account->annual_revenue)->toBeGreaterThanOrEqual(10_000)
        ->and($account->annual_revenue)->toBeLessThanOrEqual(100_000_000);
});

test('account factory generates valid employee count', function (): void {
    $account = Account::factory()->create();

    expect($account->employee_count)->toBeGreaterThanOrEqual(1)
        ->and($account->employee_count)->toBeLessThanOrEqual(10_000);
});

test('account factory generates valid website url', function (): void {
    $account = Account::factory()->create();

    expect($account->website)->toBeString()
        ->and(filter_var($account->website, FILTER_VALIDATE_URL))->not->toBeFalse();
});

test('account factory generates custom fields with rating', function (): void {
    $account = Account::factory()->create();

    expect($account->custom_fields)->toBeArray()
        ->and($account->custom_fields)->toHaveKey('rating')
        ->and($account->custom_fields['rating'])->toBeGreaterThanOrEqual(1)
        ->and($account->custom_fields['rating'])->toBeLessThanOrEqual(5);
});

test('account factory can create multiple accounts', function (): void {
    $accounts = Account::factory()->count(5)->create();

    expect($accounts)->toHaveCount(5)
        ->and(Account::count())->toBe(5);
});
