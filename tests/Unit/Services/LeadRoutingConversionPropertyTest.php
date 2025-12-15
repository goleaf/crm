<?php

declare(strict_types=1);

use App\Enums\LeadAssignmentStrategy;
use App\Enums\LeadStatus;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Team;
use App\Models\Territory;
use App\Models\TerritoryAssignment;
use App\Models\User;
use App\Services\LeadAssignmentService;
use App\Services\LeadConversionService;
use App\Services\TerritoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

/**
 * **Feature: core-crm-modules, Property 3: Lead distribution rules**
 *
 * **Validates: Requirements 3.2**
 *
 * Property: Round-robin and territory assignment must deterministically assign
 * new leads according to configured rules without skips.
 */

// Property: Round-robin distributes leads evenly across users
test('property: round-robin assignment distributes leads evenly without skips', function (): void {
    $team = Team::factory()->create();
    $users = User::factory()->count(3)->create();

    // Add users to team
    foreach ($users as $user) {
        $user->teams()->attach($team);
    }

    // Clear any cached round-robin state
    Cache::forget("lead_round_robin:{$team->id}");

    $service = new LeadAssignmentService(new TerritoryService);
    $assignments = [];

    // Create and assign multiple leads
    for ($i = 0; $i < 9; $i++) {
        $lead = Lead::factory()->create([
            'team_id' => $team->id,
            'assignment_strategy' => LeadAssignmentStrategy::ROUND_ROBIN,
        ]);

        $assignedUser = $service->assign($lead, LeadAssignmentStrategy::ROUND_ROBIN);
        $assignments[] = $assignedUser?->id;
    }

    // Each user should be assigned exactly 3 leads (9 / 3)
    $distribution = array_count_values(array_filter($assignments));

    expect($distribution)->toHaveCount(3)
        ->and(array_values($distribution))->each->toBe(3);
})->repeat(100);

// Property: Round-robin maintains sequence across multiple assignments
test('property: round-robin maintains deterministic sequence', function (): void {
    $team = Team::factory()->create();
    $users = User::factory()->count(4)->create();

    foreach ($users as $user) {
        $user->teams()->attach($team);
    }

    Cache::forget("lead_round_robin:{$team->id}");

    $service = new LeadAssignmentService(new TerritoryService);
    $sequence = [];

    // Assign 12 leads to get 3 full cycles
    for ($i = 0; $i < 12; $i++) {
        $lead = Lead::factory()->create([
            'team_id' => $team->id,
            'assignment_strategy' => LeadAssignmentStrategy::ROUND_ROBIN,
        ]);

        $assignedUser = $service->assign($lead, LeadAssignmentStrategy::ROUND_ROBIN);
        $sequence[] = $assignedUser?->id;
    }

    // Verify the pattern repeats every 4 assignments
    for ($i = 0; $i < 8; $i++) {
        expect($sequence[$i])->toBe($sequence[$i + 4]);
    }
})->repeat(50);

// Property: Territory assignment respects territory rules
test('property: territory assignment assigns leads to correct territory owners', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);

    $territory = Territory::factory()->create([
        'team_id' => $team->id,
        'is_active' => true,
        'assignment_rules' => ['state' => 'CA'],
    ]);

    TerritoryAssignment::factory()->create([
        'territory_id' => $territory->id,
        'user_id' => $user->id,
        'is_primary' => true,
    ]);

    $lead = Lead::factory()->create([
        'team_id' => $team->id,
        'assignment_strategy' => LeadAssignmentStrategy::TERRITORY,
    ]);

    $service = new LeadAssignmentService(new TerritoryService);
    $assignedUser = $service->assign($lead, LeadAssignmentStrategy::TERRITORY);

    // If territory matches, user should be assigned
    if ($territory->matchesAssignmentRules($lead)) {
        expect($assignedUser?->id)->toBe($user->id);
    }
})->repeat(50);

// Property: Weighted assignment favors users with lower load
test('property: weighted assignment assigns to users with fewer active leads', function (): void {
    $team = Team::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $user1->teams()->attach($team);
    $user2->teams()->attach($team);

    // Give user1 more leads
    Lead::factory()->count(5)->create([
        'team_id' => $team->id,
        'assigned_to_id' => $user1->id,
        'converted_at' => null,
    ]);

    // Give user2 fewer leads
    Lead::factory()->count(2)->create([
        'team_id' => $team->id,
        'assigned_to_id' => $user2->id,
        'converted_at' => null,
    ]);

    $newLead = Lead::factory()->create([
        'team_id' => $team->id,
        'assignment_strategy' => LeadAssignmentStrategy::WEIGHTED,
    ]);

    $service = new LeadAssignmentService(new TerritoryService);
    $assignedUser = $service->assign($newLead, LeadAssignmentStrategy::WEIGHTED);

    // Should assign to user2 who has fewer leads
    expect($assignedUser?->id)->toBe($user2->id);
})->repeat(50);

// Property: Assignment respects team boundaries
test('property: lead assignment only considers users within the same team', function (): void {
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $user1->teams()->attach($team1);
    $user2->teams()->attach($team2);

    Cache::forget("lead_round_robin:{$team1->id}");

    $lead = Lead::factory()->create([
        'team_id' => $team1->id,
        'assignment_strategy' => LeadAssignmentStrategy::ROUND_ROBIN,
    ]);

    $service = new LeadAssignmentService(new TerritoryService);
    $assignedUser = $service->assign($lead, LeadAssignmentStrategy::ROUND_ROBIN);

    // Should only assign to user1 from team1
    expect($assignedUser?->id)->toBe($user1->id);
})->repeat(50);

/**
 * **Feature: core-crm-modules, Property 4: Lead conversion integrity**
 *
 * **Validates: Requirements 3.5**
 *
 * Property: Converting a lead creates/links Account, Contact, Opportunity exactly
 * once and marks the lead converted atomically.
 */

// Property: Lead conversion creates exactly one company when requested
test('property: lead conversion creates exactly one company', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    Auth::login($user);

    $lead = Lead::factory()->create([
        'team_id' => $team->id,
        'company_name' => fake()->company(),
    ]);

    $initialCompanyCount = Company::count();

    $service = new LeadConversionService;
    $result = $service->convert($lead, [
        'new_company_name' => $lead->company_name,
        'create_contact' => false,
        'create_opportunity' => false,
    ]);

    $finalCompanyCount = Company::count();

    expect($result->company)->not->toBeNull()
        ->and($finalCompanyCount - $initialCompanyCount)->toBe(1)
        ->and($lead->fresh()->converted_company_id)->toBe($result->company->id);
})->repeat(100);

// Property: Lead conversion creates exactly one contact when requested
test('property: lead conversion creates exactly one contact when requested', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    Auth::login($user);

    $lead = Lead::factory()->create([
        'team_id' => $team->id,
        'name' => fake()->name(),
    ]);

    $initialContactCount = People::count();

    $service = new LeadConversionService;
    $result = $service->convert($lead, [
        'new_company_name' => fake()->company(),
        'create_contact' => true,
        'contact_name' => $lead->name,
        'create_opportunity' => false,
    ]);

    $finalContactCount = People::count();

    expect($result->contact)->not->toBeNull()
        ->and($finalContactCount - $initialContactCount)->toBe(1)
        ->and($lead->fresh()->converted_contact_id)->toBe($result->contact->id);
})->repeat(100);

// Property: Lead conversion creates exactly one opportunity when requested
test('property: lead conversion creates exactly one opportunity when requested', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    Auth::login($user);

    $lead = Lead::factory()->create([
        'team_id' => $team->id,
        'name' => fake()->name(),
    ]);

    $initialOpportunityCount = Opportunity::count();

    $service = new LeadConversionService;
    $result = $service->convert($lead, [
        'new_company_name' => fake()->company(),
        'create_contact' => false,
        'create_opportunity' => true,
        'opportunity_name' => $lead->name,
    ]);

    $finalOpportunityCount = Opportunity::count();

    expect($result->opportunity)->not->toBeNull()
        ->and($finalOpportunityCount - $initialOpportunityCount)->toBe(1)
        ->and($lead->fresh()->converted_opportunity_id)->toBe($result->opportunity->id);
})->repeat(100);

// Property: Lead conversion marks lead as converted atomically
test('property: lead conversion atomically updates lead status and timestamps', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    Auth::login($user);

    $lead = Lead::factory()->create([
        'team_id' => $team->id,
        'status' => LeadStatus::QUALIFIED,
        'converted_at' => null,
    ]);

    $service = new LeadConversionService;
    $service->convert($lead, [
        'new_company_name' => fake()->company(),
        'create_contact' => false,
        'create_opportunity' => false,
    ]);

    $lead->refresh();

    expect($lead->status)->toBe(LeadStatus::CONVERTED)
        ->and($lead->converted_at)->not->toBeNull()
        ->and($lead->converted_by_id)->toBe($user->id)
        ->and($lead->isConverted())->toBeTrue();
})->repeat(100);

// Property: Lead conversion links all created records bidirectionally
test('property: lead conversion creates bidirectional links between records', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    Auth::login($user);

    $lead = Lead::factory()->create([
        'team_id' => $team->id,
        'name' => fake()->name(),
    ]);

    $service = new LeadConversionService;
    $result = $service->convert($lead, [
        'new_company_name' => fake()->company(),
        'create_contact' => true,
        'contact_name' => $lead->name,
        'create_opportunity' => true,
        'opportunity_name' => $lead->name,
    ]);

    $lead->refresh();

    // Verify lead links to all created records
    expect($lead->converted_company_id)->toBe($result->company->id)
        ->and($lead->converted_contact_id)->toBe($result->contact->id)
        ->and($lead->converted_opportunity_id)->toBe($result->opportunity->id);

    // Verify contact links to company
    expect($result->contact->company_id)->toBe($result->company->id);

    // Verify opportunity links to company and contact
    expect($result->opportunity->company_id)->toBe($result->company->id)
        ->and($result->opportunity->contact_id)->toBe($result->contact->id);
})->repeat(100);

// Property: Lead conversion prevents double conversion
test('property: lead conversion prevents converting the same lead twice', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    Auth::login($user);

    $lead = Lead::factory()->create([
        'team_id' => $team->id,
    ]);

    $service = new LeadConversionService;

    // First conversion should succeed
    $service->convert($lead, [
        'new_company_name' => fake()->company(),
    ]);

    // Second conversion should throw exception
    expect(fn (): \App\Services\LeadConversionResult => $service->convert($lead->fresh(), [
        'new_company_name' => fake()->company(),
    ]))->toThrow(\RuntimeException::class);
})->repeat(50);

// Property: Lead conversion is transactional
test('property: lead conversion is atomic - all or nothing', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    Auth::login($user);

    $lead = Lead::factory()->create([
        'team_id' => $team->id,
        'converted_at' => null,
    ]);

    $service = new LeadConversionService;

    // First conversion should succeed
    $result = $service->convert($lead, [
        'new_company_name' => fake()->company(),
        'create_contact' => true,
        'contact_name' => fake()->name(),
        'create_opportunity' => true,
        'opportunity_name' => fake()->name(),
    ]);

    // Verify all records were created together
    expect($result->company)->not->toBeNull()
        ->and($result->contact)->not->toBeNull()
        ->and($result->opportunity)->not->toBeNull()
        ->and($lead->fresh()->isConverted())->toBeTrue()
        ->and($lead->fresh()->converted_company_id)->toBe($result->company->id)
        ->and($lead->fresh()->converted_contact_id)->toBe($result->contact->id)
        ->and($lead->fresh()->converted_opportunity_id)->toBe($result->opportunity->id);

    // Attempting to convert again should fail without creating duplicates
    $initialCompanyCount = Company::count();
    $initialContactCount = People::count();
    $initialOpportunityCount = Opportunity::count();

    try {
        $service->convert($lead->fresh(), [
            'new_company_name' => fake()->company(),
        ]);
    } catch (\RuntimeException) {
        // Expected to fail
    }

    // Verify no additional records were created
    expect(Company::count())->toBe($initialCompanyCount)
        ->and(People::count())->toBe($initialContactCount)
        ->and(Opportunity::count())->toBe($initialOpportunityCount);
})->repeat(50);

// Property: Bulk assignment maintains distribution
test('property: bulk assignment maintains round-robin distribution', function (): void {
    $team = Team::factory()->create();
    $users = User::factory()->count(3)->create();

    foreach ($users as $user) {
        $user->teams()->attach($team);
    }

    Cache::forget("lead_round_robin:{$team->id}");

    $leads = Lead::factory()->count(12)->create([
        'team_id' => $team->id,
        'assignment_strategy' => LeadAssignmentStrategy::ROUND_ROBIN,
    ]);

    $service = new LeadAssignmentService(new TerritoryService);
    $results = $service->bulkAssign($leads, LeadAssignmentStrategy::ROUND_ROBIN);

    $assignments = $results->pluck('user.id')->filter()->toArray();
    $distribution = array_count_values($assignments);

    // Each user should get 4 leads (12 / 3)
    expect($distribution)->toHaveCount(3)
        ->and(array_values($distribution))->each->toBe(4);
})->repeat(50);

// Property: Reassignment preserves team boundaries
test('property: lead reassignment only affects leads within specified team', function (): void {
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    $user1->teams()->attach([$team1->id, $team2->id]);
    $user2->teams()->attach([$team1->id, $team2->id]);
    $user3->teams()->attach($team2);

    // Create leads in both teams assigned to user1
    Lead::factory()->count(3)->create([
        'team_id' => $team1->id,
        'assigned_to_id' => $user1->id,
    ]);

    Lead::factory()->count(2)->create([
        'team_id' => $team2->id,
        'assigned_to_id' => $user1->id,
    ]);

    $service = new LeadAssignmentService(new TerritoryService);

    // Reassign only team1 leads
    $count = $service->reassign($user1, $user2, $team1->id);

    expect($count)->toBe(3)
        ->and(Lead::where('team_id', $team1->id)->where('assigned_to_id', $user2->id)->count())->toBe(3)
        ->and(Lead::where('team_id', $team2->id)->where('assigned_to_id', $user1->id)->count())->toBe(2);
})->repeat(50);
