<?php

declare(strict_types=1);

use App\Enums\CreationSource;
use App\Models\Company;
use App\Models\CompanyRevenue;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('company revenue inherits creator and team from context', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($user);

    $company = Company::factory()->create([
        'team_id' => $user->personalTeam()->getKey(),
        'account_owner_id' => $user->getKey(),
    ]);

    $revenue = CompanyRevenue::create([
        'company_id' => $company->getKey(),
        'year' => now()->year,
        'amount' => 1250000.50,
        'currency_code' => 'USD',
    ]);

    expect($revenue->team_id)->toBe($company->team_id)
        ->and($revenue->creator_id)->toBe($user->getKey())
        ->and($revenue->creation_source)->toBe(CreationSource::WEB);
});

test('latest annual revenue syncs onto company summary', function (): void {
    $company = Company::factory()->create([
        'revenue' => null,
    ]);

    $previous = CompanyRevenue::factory()->create([
        'company_id' => $company->getKey(),
        'team_id' => $company->team_id,
        'year' => now()->subYear()->year,
        'amount' => 750000.00,
    ]);

    $latest = CompanyRevenue::factory()->create([
        'company_id' => $company->getKey(),
        'team_id' => $company->team_id,
        'year' => now()->year,
        'amount' => 1100000.25,
    ]);

    $company->refresh();

    expect((float) $company->revenue)->toBe((float) $latest->amount)
        ->and($company->latestAnnualRevenue?->getKey())->toBe($latest->getKey());

    $latest->delete();
    $company->refresh();

    expect((float) $company->revenue)->toBe((float) $previous->amount)
        ->and($company->latestAnnualRevenue?->getKey())->toBe($previous->getKey());
});

test('annual revenue entries are unique per company per year', function (): void {
    $company = Company::factory()->create();

    CompanyRevenue::factory()->create([
        'company_id' => $company->getKey(),
        'team_id' => $company->team_id,
        'year' => 2024,
    ]);

    expect(fn (): CompanyRevenue => CompanyRevenue::factory()->create([
        'company_id' => $company->getKey(),
        'team_id' => $company->team_id,
        'year' => 2024,
    ]))->toThrow(QueryException::class);
});