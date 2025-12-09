<?php

declare(strict_types=1);

namespace Tests\Feature\AdvancedFeatures;

use App\Enums\TerritoryOverlapResolution;
use App\Enums\TerritoryRole;
use App\Enums\TerritoryType;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Team;
use App\Models\Territory;
use App\Models\TerritoryAssignment;
use App\Models\TerritoryOverlap;
use App\Models\TerritoryQuota;
use App\Models\TerritoryRecord;
use App\Models\User;
use App\Services\TerritoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Integration test: Territory-based access control
 *
 * Tests the complete workflow of territory assignment, access control,
 * and permission enforcement across multiple users and records.
 */
test('complete territory-based access control workflow', function (): void {
    $team = Team::factory()->create();
    $service = new TerritoryService;

    // Create territories
    $westTerritory = Territory::factory()->for($team)->create([
        'name' => 'West Region',
        'type' => TerritoryType::GEOGRAPHIC,
        'assignment_rules' => [
            ['field' => 'state', 'operator' => 'in', 'value' => ['CA', 'OR', 'WA']],
        ],
        'is_active' => true,
    ]);

    $eastTerritory = Territory::factory()->for($team)->create([
        'name' => 'East Region',
        'type' => TerritoryType::GEOGRAPHIC,
        'assignment_rules' => [
            ['field' => 'state', 'operator' => 'in', 'value' => ['NY', 'MA', 'CT']],
        ],
        'is_active' => true,
    ]);

    // Create users with different roles
    $westOwner = User::factory()->create();
    $westMember = User::factory()->create();
    $eastOwner = User::factory()->create();
    $noAccessUser = User::factory()->create();

    // Assign users to territories
    TerritoryAssignment::factory()->owner()->create([
        'territory_id' => $westTerritory->id,
        'user_id' => $westOwner->id,
    ]);

    TerritoryAssignment::factory()->member()->create([
        'territory_id' => $westTerritory->id,
        'user_id' => $westMember->id,
    ]);

    TerritoryAssignment::factory()->owner()->create([
        'territory_id' => $eastTerritory->id,
        'user_id' => $eastOwner->id,
    ]);

    // Create leads in different territories
    $westLead = Lead::factory()->create([
        'team_id' => $team->id,
        'state' => 'CA',
        'company' => 'West Coast Inc',
    ]);

    $eastLead = Lead::factory()->create([
        'team_id' => $team->id,
        'state' => 'NY',
        'company' => 'East Coast LLC',
    ]);

    // Assign leads to territories
    $westAssignment = $service->assignRecord($westLead, $westTerritory);
    $eastAssignment = $service->assignRecord($eastLead, $eastTerritory);

    expect($westAssignment->territory_id)->toBe($westTerritory->id)
        ->and($eastAssignment->territory_id)->toBe($eastTerritory->id);

    // Test access control
    // West owner should have access to west lead
    expect($service->userHasAccess($westOwner, $westLead))->toBeTrue()
        ->and($service->userHasAccess($westOwner, $westLead, TerritoryRole::OWNER))->toBeTrue();

    // West member should have access to west lead but not owner-level
    expect($service->userHasAccess($westMember, $westLead))->toBeTrue()
        ->and($service->userHasAccess($westMember, $westLead, TerritoryRole::OWNER))->toBeFalse()
        ->and($service->userHasAccess($westMember, $westLead, TerritoryRole::MEMBER))->toBeTrue();

    // West users should NOT have access to east lead
    expect($service->userHasAccess($westOwner, $eastLead))->toBeFalse()
        ->and($service->userHasAccess($westMember, $eastLead))->toBeFalse();

    // East owner should have access to east lead
    expect($service->userHasAccess($eastOwner, $eastLead))->toBeTrue()
        ->and($service->userHasAccess($eastOwner, $eastLead, TerritoryRole::OWNER))->toBeTrue();

    // User with no territory assignment should have no access
    expect($service->userHasAccess($noAccessUser, $westLead))->toBeFalse()
        ->and($service->userHasAccess($noAccessUser, $eastLead))->toBeFalse();

    // Get accessible records for each user
    $westOwnerLeads = $service->getAccessibleRecords($westOwner, Lead::class);
    expect($westOwnerLeads)->toHaveCount(1)
        ->and($westOwnerLeads->first()->id)->toBe($westLead->id);

    $eastOwnerLeads = $service->getAccessibleRecords($eastOwner, Lead::class);
    expect($eastOwnerLeads)->toHaveCount(1)
        ->and($eastOwnerLeads->first()->id)->toBe($eastLead->id);

    $noAccessLeads = $service->getAccessibleRecords($noAccessUser, Lead::class);
    expect($noAccessLeads)->toHaveCount(0);
});

/**
 * Integration test: Territory overlap resolution
 */
test('territory overlaps are resolved according to strategy', function (): void {
    $team = Team::factory()->create();
    $service = new TerritoryService;

    // Create two overlapping territories
    $territoryA = Territory::factory()->for($team)->create([
        'name' => 'California Territory',
        'assignment_rules' => [
            ['field' => 'state', 'operator' => '=', 'value' => 'CA'],
        ],
        'is_active' => true,
    ]);

    $territoryB = Territory::factory()->for($team)->create([
        'name' => 'Tech Companies Territory',
        'assignment_rules' => [
            ['field' => 'state', 'operator' => '=', 'value' => 'CA'],
            ['field' => 'industry', 'operator' => '=', 'value' => 'Technology'],
        ],
        'is_active' => true,
    ]);

    // Define overlap with priority resolution
    $overlap = TerritoryOverlap::factory()->create([
        'territory_a_id' => $territoryA->id,
        'territory_b_id' => $territoryB->id,
        'resolution_strategy' => TerritoryOverlapResolution::PRIORITY,
        'priority_territory_id' => $territoryB->id, // Tech territory has priority
    ]);

    // Create lead that matches both territories
    $lead = Lead::factory()->create([
        'team_id' => $team->id,
        'state' => 'CA',
        'industry' => 'Technology',
    ]);

    // Assign - should resolve to priority territory (B)
    $assignment = $service->assignRecord($lead, $territoryB);

    expect($assignment->territory_id)->toBe($territoryB->id)
        ->and($assignment->is_primary)->toBeTrue();

    // Verify overlap is recorded
    expect($overlap->fresh()->overlap_count)->toBeGreaterThan(0);
});

/**
 * Integration test: Territory transfer workflow
 */
test('territory transfer maintains audit trail', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $service = new TerritoryService;

    $fromTerritory = Territory::factory()->for($team)->create(['name' => 'Territory A']);
    $toTerritory = Territory::factory()->for($team)->create(['name' => 'Territory B']);

    // Create and assign lead
    $lead = Lead::factory()->create(['team_id' => $team->id]);
    TerritoryRecord::factory()->create([
        'territory_id' => $fromTerritory->id,
        'record_type' => Lead::class,
        'record_id' => $lead->id,
    ]);

    // Transfer
    $transfer = $service->transferRecord(
        $lead,
        $fromTerritory,
        $toTerritory,
        $user,
        'Territory rebalancing',
    );

    expect($transfer)->not->toBeNull()
        ->and($transfer->from_territory_id)->toBe($fromTerritory->id)
        ->and($transfer->to_territory_id)->toBe($toTerritory->id)
        ->and($transfer->transferred_by_id)->toBe($user->id)
        ->and($transfer->reason)->toBe('Territory rebalancing')
        ->and($transfer->transferred_at)->not->toBeNull();

    // Verify record is now in new territory
    $record = TerritoryRecord::where('record_type', Lead::class)
        ->where('record_id', $lead->id)
        ->first();

    expect($record->territory_id)->toBe($toTerritory->id);
});

/**
 * Integration test: Territory hierarchy and inheritance
 */
test('territory hierarchy maintains parent-child relationships', function (): void {
    $team = Team::factory()->create();

    // Create parent territory
    $parent = Territory::factory()->for($team)->create([
        'name' => 'North America',
        'level' => 0,
        'path' => '1',
    ]);

    // Create child territories
    $child1 = Territory::factory()->for($team)->create([
        'name' => 'United States',
        'parent_id' => $parent->id,
        'level' => 1,
        'path' => '1/2',
    ]);

    $child2 = Territory::factory()->for($team)->create([
        'name' => 'Canada',
        'parent_id' => $parent->id,
        'level' => 1,
        'path' => '1/3',
    ]);

    // Create grandchild
    $grandchild = Territory::factory()->for($team)->create([
        'name' => 'California',
        'parent_id' => $child1->id,
        'level' => 2,
        'path' => '1/2/4',
    ]);

    // Verify relationships
    expect($parent->children)->toHaveCount(2)
        ->and($parent->children->pluck('id')->toArray())->toContain($child1->id, $child2->id)
        ->and($child1->parent->id)->toBe($parent->id)
        ->and($child1->children)->toHaveCount(1)
        ->and($child1->children->first()->id)->toBe($grandchild->id)
        ->and($grandchild->parent->id)->toBe($child1->id);
});

/**
 * Integration test: Territory quota tracking
 */
test('territory quotas track performance against targets', function (): void {
    $team = Team::factory()->create();

    $territory = Territory::factory()->for($team)->create();

    // Set quarterly quota
    $quota = TerritoryQuota::factory()->create([
        'territory_id' => $territory->id,
        'period_start' => now()->startOfQuarter(),
        'period_end' => now()->endOfQuarter(),
        'target_amount' => 100000.00,
        'actual_amount' => 0,
    ]);

    // Create opportunities in territory
    $opp1 = Opportunity::factory()->create([
        'team_id' => $team->id,
        'amount' => 25000.00,
    ]);

    $opp2 = Opportunity::factory()->create([
        'team_id' => $team->id,
        'amount' => 35000.00,
    ]);

    TerritoryRecord::factory()->create([
        'territory_id' => $territory->id,
        'record_type' => Opportunity::class,
        'record_id' => $opp1->id,
    ]);

    TerritoryRecord::factory()->create([
        'territory_id' => $territory->id,
        'record_type' => Opportunity::class,
        'record_id' => $opp2->id,
    ]);

    // Update quota with actual amount
    $quota->update(['actual_amount' => 60000.00]);

    expect($quota->fresh()->actual_amount)->toBe(60000.00)
        ->and($quota->target_amount)->toBe(100000.00)
        ->and($quota->attainment_percentage)->toBe(60.0);
});

/**
 * Integration test: Territory balancing
 */
test('territory balancing calculates distribution', function (): void {
    $team = Team::factory()->create();
    $service = new TerritoryService;

    $territory1 = Territory::factory()->for($team)->create(['name' => 'Territory 1']);
    $territory2 = Territory::factory()->for($team)->create(['name' => 'Territory 2']);

    // Create unbalanced distribution
    for ($i = 0; $i < 15; $i++) {
        $lead = Lead::factory()->create(['team_id' => $team->id]);
        TerritoryRecord::factory()->create([
            'territory_id' => $territory1->id,
            'record_type' => Lead::class,
            'record_id' => $lead->id,
        ]);
    }

    for ($i = 0; $i < 5; $i++) {
        $lead = Lead::factory()->create(['team_id' => $team->id]);
        TerritoryRecord::factory()->create([
            'territory_id' => $territory2->id,
            'record_type' => Lead::class,
            'record_id' => $lead->id,
        ]);
    }

    // Calculate balance
    $balance = $service->balanceTerritories(collect([$territory1, $territory2]));

    expect($balance[$territory1->id]['current'])->toBe(15)
        ->and($balance[$territory2->id]['current'])->toBe(5)
        ->and($balance[$territory1->id]['target'])->toBe(10)
        ->and($balance[$territory2->id]['target'])->toBe(10)
        ->and($balance[$territory1->id]['difference'])->toBe(-5)
        ->and($balance[$territory2->id]['difference'])->toBe(5);
});
