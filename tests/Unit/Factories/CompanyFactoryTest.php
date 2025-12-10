<?php

declare(strict_types=1);

use App\Enums\AccountType;
use App\Enums\AddressType;
use App\Enums\Industry;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('company factory creates valid company with all required fields', function (): void {
    $company = Company::factory()->create();

    expect($company)->toBeInstanceOf(Company::class)
        ->and($company->name)->not->toBeNull()
        ->and($company->team_id)->not->toBeNull()
        ->and($company->account_owner_id)->not->toBeNull()
        ->and($company->account_type)->not->toBeNull();
});

test('company factory generates valid billing address with postal code', function (): void {
    $company = Company::factory()->create();

    expect($company->billing_postal_code)->not->toBeNull()
        ->and($company->billing_postal_code)->toBeString()
        ->and($company->billing_street)->not->toBeNull()
        ->and($company->billing_city)->not->toBeNull()
        ->and($company->billing_state)->not->toBeNull()
        ->and($company->billing_country)->toBe('US');
});

test('company factory generates valid shipping address with postal code', function (): void {
    $company = Company::factory()->create();

    expect($company->shipping_postal_code)->not->toBeNull()
        ->and($company->shipping_postal_code)->toBeString()
        ->and($company->shipping_street)->not->toBeNull()
        ->and($company->shipping_city)->not->toBeNull()
        ->and($company->shipping_state)->not->toBeNull()
        ->and($company->shipping_country)->toBe('US');
});

test('company factory generates valid addresses array with both billing and shipping', function (): void {
    $company = Company::factory()->create();

    expect($company->addresses)->toBeArray()
        ->and($company->addresses)->toHaveCount(2);

    $billingAddress = collect($company->addresses)->firstWhere('type', AddressType::BILLING->value);
    $shippingAddress = collect($company->addresses)->firstWhere('type', AddressType::SHIPPING->value);

    expect($billingAddress)->not->toBeNull()
        ->and($billingAddress['postal_code'])->not->toBeNull()
        ->and($billingAddress['postal_code'])->toBeString()
        ->and($shippingAddress)->not->toBeNull()
        ->and($shippingAddress['postal_code'])->not->toBeNull()
        ->and($shippingAddress['postal_code'])->toBeString();
});

test('company factory generates valid social links', function (): void {
    $company = Company::factory()->create();

    expect($company->social_links)->toBeArray()
        ->and($company->social_links)->toHaveKeys(['linkedin', 'twitter']);
});

test('company factory generates valid currency from config', function (): void {
    $company = Company::factory()->create();
    $validCurrencies = array_keys(config('company.currency_codes', []));

    if ($validCurrencies !== []) {
        expect($validCurrencies)->toContain($company->currency_code);
    }
});

test('company factory creates associated team and owner', function (): void {
    $company = Company::factory()->create();

    expect($company->team)->toBeInstanceOf(Team::class)
        ->and($company->accountOwner)->toBeInstanceOf(User::class);
});

test('company factory can override default values', function (): void {
    $customName = 'Custom Company Name';
    $customType = AccountType::PARTNER;

    $company = Company::factory()->create([
        'name' => $customName,
        'account_type' => $customType->value,
    ]);

    // account_type may be cast to enum depending on model casts
    $accountTypeValue = $company->account_type instanceof AccountType
        ? $company->account_type->value
        : $company->account_type;

    expect($company->name)->toBe($customName)
        ->and($accountTypeValue)->toBe($customType->value);
});

test('company factory generates valid revenue', function (): void {
    $company = Company::factory()->create();

    expect($company->revenue)->toBeGreaterThanOrEqual(100_000)
        ->and($company->revenue)->toBeLessThanOrEqual(100_000_000);
});

test('company factory generates valid employee count', function (): void {
    $company = Company::factory()->create();

    expect($company->employee_count)->toBeGreaterThanOrEqual(5)
        ->and($company->employee_count)->toBeLessThanOrEqual(5_000);
});

test('company factory generates valid website url', function (): void {
    $company = Company::factory()->create();

    expect($company->website)->toBeString()
        ->and(filter_var($company->website, FILTER_VALIDATE_URL))->not->toBeFalse();
});

test('company factory generates valid phone number', function (): void {
    $company = Company::factory()->create();

    expect($company->phone)->toBeString()
        ->and($company->phone)->toStartWith('+');
});

test('company factory generates valid email', function (): void {
    $company = Company::factory()->create();

    expect($company->primary_email)->toBeString()
        ->and(filter_var($company->primary_email, FILTER_VALIDATE_EMAIL))->not->toBeFalse();
});

test('company factory generates description', function (): void {
    $company = Company::factory()->create();

    expect($company->description)->toBeString()
        ->and($company->description)->not->toBeEmpty();
});

test('company factory can create multiple companies', function (): void {
    $companies = Company::factory()->count(5)->create();

    expect($companies)->toHaveCount(5)
        ->and(Company::count())->toBe(5);
});

test('company factory generates valid industry', function (): void {
    $company = Company::factory()->create();
    $validIndustries = array_column(Industry::cases(), 'value');

    // Industry may be cast to enum or stored as string depending on model casts
    $industryValue = $company->industry instanceof Industry
        ? $company->industry->value
        : $company->industry;

    expect($validIndustries)->toContain($industryValue);
});

test('company factory generates valid ownership type', function (): void {
    $company = Company::factory()->create();
    $validOwnershipTypes = array_keys(config('company.ownership_types', []));

    if ($validOwnershipTypes !== []) {
        expect($validOwnershipTypes)->toContain($company->ownership);
    }
});
