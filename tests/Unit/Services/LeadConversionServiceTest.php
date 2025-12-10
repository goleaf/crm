<?php

declare(strict_types=1);

use App\Enums\LeadStatus;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Team;
use App\Models\User;
use App\Services\LeadConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

test('converts lead and creates company', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    Auth::login($user);

    $lead = Lead::factory()->create([
        'team_id' => $team->id,
        'company_name' => 'Test Company',
    ]);

    $service = new LeadConversionService;
    $result = $service->convert($lead, [
        'new_company_name' => $lead->company_name,
    ]);

    expect($result->company)->toBeInstanceOf(Company::class)
        ->and($result->company->name)->toBe('Test Company')
        ->and($lead->fresh()->converted_company_id)->toBe($result->company->id);
});

test('converts lead and creates contact', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    Auth::login($user);

    $lead = Lead::factory()->create([
        'team_id' => $team->id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $service = new LeadConversionService;
    $result = $service->convert($lead, [
        'new_company_name' => 'Test Company',
        'create_contact' => true,
        'contact_name' => $lead->name,
        'contact_email' => $lead->email,
    ]);

    expect($result->contact)->toBeInstanceOf(People::class)
        ->and($result->contact->name)->toBe('John Doe')
        ->and($result->contact->primary_email)->toBe('john@example.com')
        ->and($lead->fresh()->converted_contact_id)->toBe($result->contact->id);
});

test('converts lead and creates opportunity', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    Auth::login($user);

    $lead = Lead::factory()->create([
        'team_id' => $team->id,
        'name' => 'Big Deal',
    ]);

    $service = new LeadConversionService;
    $result = $service->convert($lead, [
        'new_company_name' => 'Test Company',
        'create_opportunity' => true,
        'opportunity_name' => $lead->name,
    ]);

    expect($result->opportunity)->toBeInstanceOf(Opportunity::class)
        ->and($result->opportunity->name)->toBe('Big Deal')
        ->and($lead->fresh()->converted_opportunity_id)->toBe($result->opportunity->id);
});

test('marks lead as converted', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    Auth::login($user);

    $lead = Lead::factory()->create([
        'team_id' => $team->id,
        'status' => LeadStatus::QUALIFIED,
    ]);

    $service = new LeadConversionService;
    $service->convert($lead, [
        'new_company_name' => 'Test Company',
    ]);

    $lead->refresh();

    expect($lead->status)->toBe(LeadStatus::CONVERTED)
        ->and($lead->converted_at)->not->toBeNull()
        ->and($lead->converted_by_id)->toBe($user->id)
        ->and($lead->isConverted())->toBeTrue();
});

test('links contact to company', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    Auth::login($user);

    $lead = Lead::factory()->create([
        'team_id' => $team->id,
    ]);

    $service = new LeadConversionService;
    $result = $service->convert($lead, [
        'new_company_name' => 'Test Company',
        'create_contact' => true,
        'contact_name' => 'John Doe',
    ]);

    expect($result->contact->company_id)->toBe($result->company->id);
});

test('links opportunity to company and contact', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    Auth::login($user);

    $lead = Lead::factory()->create([
        'team_id' => $team->id,
    ]);

    $service = new LeadConversionService;
    $result = $service->convert($lead, [
        'new_company_name' => 'Test Company',
        'create_contact' => true,
        'contact_name' => 'John Doe',
        'create_opportunity' => true,
        'opportunity_name' => 'Big Deal',
    ]);

    expect($result->opportunity->company_id)->toBe($result->company->id)
        ->and($result->opportunity->contact_id)->toBe($result->contact->id);
});

test('uses existing company when company_id provided', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    Auth::login($user);

    $existingCompany = Company::factory()->create([
        'team_id' => $team->id,
    ]);

    $lead = Lead::factory()->create([
        'team_id' => $team->id,
    ]);

    $service = new LeadConversionService;
    $result = $service->convert($lead, [
        'company_id' => $existingCompany->id,
    ]);

    expect($result->company->id)->toBe($existingCompany->id)
        ->and(Company::count())->toBe(1); // No new company created
});

test('prevents double conversion', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    Auth::login($user);

    $lead = Lead::factory()->create([
        'team_id' => $team->id,
    ]);

    $service = new LeadConversionService;
    $service->convert($lead, [
        'new_company_name' => 'Test Company',
    ]);

    expect(fn (): \App\Services\LeadConversionResult => $service->convert($lead->fresh(), [
        'new_company_name' => 'Another Company',
    ]))->toThrow(\RuntimeException::class, 'already been converted');
});

test('conversion respects team boundaries', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    Auth::login($user);

    $lead = Lead::factory()->create([
        'team_id' => $team->id,
    ]);

    $service = new LeadConversionService;
    $result = $service->convert($lead, [
        'new_company_name' => 'Test Company',
        'create_contact' => true,
        'contact_name' => 'John Doe',
        'create_opportunity' => true,
        'opportunity_name' => 'Big Deal',
    ]);

    expect($result->company->team_id)->toBe($team->id)
        ->and($result->contact->team_id)->toBe($team->id)
        ->and($result->opportunity->team_id)->toBe($team->id);
});