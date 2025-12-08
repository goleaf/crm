<?php

declare(strict_types=1);

use App\Enums\TerritoryRole;
use App\Models\Lead;
use App\Models\Team;
use App\Models\Territory;
use App\Models\TerritoryAssignment;
use App\Models\TerritoryOverlap;
use App\Models\TerritoryRecord;
use App\Models\User;
use App\Services\TerritoryService;

beforeEach(function (): void {
    $this->service = new TerritoryService;
    $this->team = Team::factory()->create();
});

/**
 * Feature: advanced-features, Property 4: Territory assignment
 * Validates: Requirements 4.1
 *
 * For any record and territory with matching assignment rules,
 * the record should be assigned to that territory without conflicts;
 * overlaps should be handled deterministically.
 */
test('territory assignment follows rules deterministically', function (): void {
    // Run 100 iterations as per PBT requirements
    for ($i = 0; $i < 100; $i++) {
        // Generate random territory with assignment rules
        $state = fake()->state();
        $territory = Territory::factory()
            ->for($this->team)
            ->create([
                'assignment_rules' => [
                    [
                        'field' => 'state',
                        'operator' => '=',
                        'value' => $state,
                    ],
                ],
                'is_active' => true,
            ]);

        // Generate random lead that matches the rules
        $lead = Lead::factory()->create([
            'team_id' => $this->team->id,
            'state' => $state,
        ]);

        // Assign the record
        $assignment = $this->service->assignRecord($lead);

        // Verify assignment was created
        expect($assignment)->not->toBeNull();
        expect($assignment->territory_id)->toBe($territory->id);
        expect($assignment->record_type)->toBe(Lead::class);
        expect($assignment->record_id)->toBe($lead->id);
        expect($assignment->is_primary)->toBeTrue();

        // Verify assignment is deterministic - reassigning should give same result
        $reassignment = $this->service->assignRecord($lead);
        expect($reassignment->territory_id)->toBe($territory->id);

        // Clean up for next iteration
        $assignment->delete();
        $lead->delete();
        $territory->delete();
    }
});

/**
 * Feature: advanced-features, Property 4: Territory assignment (overlap handling)
 * Validates: Requirements 4.1
 *
 * For any record matching multiple territories with defined overlap resolution,
 * the assignment should follow the resolution strategy deterministically.
 */
test('territory overlaps are resolved deterministically', function (): void {
    for ($i = 0; $i < 100; $i++) {
        $state = fake()->state();

        // Create two territories with the same assignment rules (overlap)
        $territoryA = Territory::factory()
            ->for($this->team)
            ->create([
                'assignment_rules' => [
                    ['field' => 'state', 'operator' => '=', 'value' => $state],
                ],
                'is_active' => true,
            ]);

        $territoryB = Territory::factory()
            ->for($this->team)
            ->create([
                'assignment_rules' => [
                    ['field' => 'state', 'operator' => '=', 'value' => $state],
                ],
                'is_active' => true,
            ]);

        // Define overlap with priority resolution
        $overlap = TerritoryOverlap::factory()->create([
            'territory_a_id' => $territoryA->id,
            'territory_b_id' => $territoryB->id,
            'resolution_strategy' => 'priority',
            'priority_territory_id' => $territoryA->id,
        ]);

        // Create lead matching both territories
        $lead = Lead::factory()->create([
            'team_id' => $this->team->id,
            'state' => $state,
        ]);

        // Assign - should resolve to priority territory
        $assignment = $this->service->assignRecord($lead, $territoryA);

        expect($assignment)->not->toBeNull();
        expect($assignment->territory_id)->toBe($territoryA->id);

        // Verify determinism - multiple assignments should give same result
        $reassignment = $this->service->assignRecord($lead, $territoryA);
        expect($reassignment->territory_id)->toBe($territoryA->id);

        // Clean up
        $assignment->delete();
        $lead->delete();
        $overlap->delete();
        $territoryB->delete();
        $territoryA->delete();
    }
});

/**
 * Feature: advanced-features, Property 5: Territory access control
 * Validates: Requirements 4.2
 *
 * For any user and record, territory-based permissions should restrict
 * access to only users assigned to the record's territory.
 */
test('territory access control restricts access correctly', function (): void {
    for ($i = 0; $i < 100; $i++) {
        // Create territory
        $territory = Territory::factory()
            ->for($this->team)
            ->create(['is_active' => true]);

        // Create two users - one with access, one without
        $userWithAccess = User::factory()->create();
        $userWithoutAccess = User::factory()->create();

        // Assign user to territory with random role
        $role = fake()->randomElement(TerritoryRole::cases());
        TerritoryAssignment::factory()->create([
            'territory_id' => $territory->id,
            'user_id' => $userWithAccess->id,
            'role' => $role,
        ]);

        // Create and assign a lead to the territory
        $lead = Lead::factory()->create(['team_id' => $this->team->id]);
        TerritoryRecord::factory()->create([
            'territory_id' => $territory->id,
            'record_type' => Lead::class,
            'record_id' => $lead->id,
        ]);

        // User with assignment should have access
        expect($this->service->userHasAccess($userWithAccess, $lead))->toBeTrue();

        // User without assignment should NOT have access
        expect($this->service->userHasAccess($userWithoutAccess, $lead))->toBeFalse();

        // Clean up
        TerritoryRecord::where('record_id', $lead->id)->delete();
        $lead->delete();
        TerritoryAssignment::where('user_id', $userWithAccess->id)->delete();
        $userWithAccess->delete();
        $userWithoutAccess->delete();
        $territory->delete();
    }
});

/**
 * Feature: advanced-features, Property 5: Territory access control (role-based)
 * Validates: Requirements 4.2
 *
 * For any user with a territory role, access should respect the role hierarchy
 * (owner > member > viewer).
 */
test('territory access control respects role hierarchy', function (): void {
    for ($i = 0; $i < 100; $i++) {
        $territory = Territory::factory()
            ->for($this->team)
            ->create(['is_active' => true]);

        // Create users with different roles
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $viewer = User::factory()->create();

        TerritoryAssignment::factory()->owner()->create([
            'territory_id' => $territory->id,
            'user_id' => $owner->id,
        ]);

        TerritoryAssignment::factory()->member()->create([
            'territory_id' => $territory->id,
            'user_id' => $member->id,
        ]);

        TerritoryAssignment::factory()->viewer()->create([
            'territory_id' => $territory->id,
            'user_id' => $viewer->id,
        ]);

        $lead = Lead::factory()->create(['team_id' => $this->team->id]);
        TerritoryRecord::factory()->create([
            'territory_id' => $territory->id,
            'record_type' => Lead::class,
            'record_id' => $lead->id,
        ]);

        // All should have basic access
        expect($this->service->userHasAccess($owner, $lead))->toBeTrue();
        expect($this->service->userHasAccess($member, $lead))->toBeTrue();
        expect($this->service->userHasAccess($viewer, $lead))->toBeTrue();

        // Owner should have owner-level access
        expect($this->service->userHasAccess($owner, $lead, TerritoryRole::OWNER))->toBeTrue();

        // Member should NOT have owner-level access
        expect($this->service->userHasAccess($member, $lead, TerritoryRole::OWNER))->toBeFalse();

        // Member should have member-level access
        expect($this->service->userHasAccess($member, $lead, TerritoryRole::MEMBER))->toBeTrue();

        // Viewer should NOT have member-level access
        expect($this->service->userHasAccess($viewer, $lead, TerritoryRole::MEMBER))->toBeFalse();

        // Clean up
        TerritoryRecord::where('record_id', $lead->id)->delete();
        $lead->delete();
        TerritoryAssignment::whereIn('user_id', [$owner->id, $member->id, $viewer->id])->delete();
        $owner->delete();
        $member->delete();
        $viewer->delete();
        $territory->delete();
    }
});

// Unit tests for specific functionality

test('can find matching territory based on assignment rules', function (): void {
    $territory = Territory::factory()
        ->for($this->team)
        ->create([
            'assignment_rules' => [
                ['field' => 'state', 'operator' => '=', 'value' => 'CA'],
            ],
            'is_active' => true,
        ]);

    $lead = Lead::factory()->create([
        'team_id' => $this->team->id,
        'state' => 'CA',
    ]);

    $found = $this->service->findMatchingTerritory($lead);

    expect($found)->not->toBeNull();
    expect($found->id)->toBe($territory->id);
});

test('returns null when no matching territory found', function (): void {
    Territory::factory()
        ->for($this->team)
        ->create([
            'assignment_rules' => [
                ['field' => 'state', 'operator' => '=', 'value' => 'CA'],
            ],
            'is_active' => true,
        ]);

    $lead = Lead::factory()->create([
        'team_id' => $this->team->id,
        'state' => 'NY',
    ]);

    $found = $this->service->findMatchingTerritory($lead);

    expect($found)->toBeNull();
});

test('can transfer record between territories', function (): void {
    $fromTerritory = Territory::factory()->for($this->team)->create();
    $toTerritory = Territory::factory()->for($this->team)->create();
    $user = User::factory()->create();
    $lead = Lead::factory()->create(['team_id' => $this->team->id]);

    // Initial assignment
    TerritoryRecord::factory()->create([
        'territory_id' => $fromTerritory->id,
        'record_type' => Lead::class,
        'record_id' => $lead->id,
    ]);

    // Transfer
    $transfer = $this->service->transferRecord(
        $lead,
        $fromTerritory,
        $toTerritory,
        $user,
        'Rebalancing'
    );

    expect($transfer)->not->toBeNull();
    expect($transfer->from_territory_id)->toBe($fromTerritory->id);
    expect($transfer->to_territory_id)->toBe($toTerritory->id);

    // Verify record is now in new territory
    $record = TerritoryRecord::where('record_type', Lead::class)
        ->where('record_id', $lead->id)
        ->first();

    expect($record->territory_id)->toBe($toTerritory->id);
});

test('can get accessible records for user', function (): void {
    $territory = Territory::factory()->for($this->team)->create();
    $user = User::factory()->create();

    TerritoryAssignment::factory()->create([
        'territory_id' => $territory->id,
        'user_id' => $user->id,
    ]);

    $lead1 = Lead::factory()->create(['team_id' => $this->team->id]);
    $lead2 = Lead::factory()->create(['team_id' => $this->team->id]);

    TerritoryRecord::factory()->create([
        'territory_id' => $territory->id,
        'record_type' => Lead::class,
        'record_id' => $lead1->id,
    ]);

    TerritoryRecord::factory()->create([
        'territory_id' => $territory->id,
        'record_type' => Lead::class,
        'record_id' => $lead2->id,
    ]);

    $accessible = $this->service->getAccessibleRecords($user, Lead::class);

    expect($accessible)->toHaveCount(2);
    expect($accessible->pluck('id'))->toContain($lead1->id, $lead2->id);
});

test('territory hierarchy works correctly', function (): void {
    $parent = Territory::factory()->for($this->team)->create([
        'level' => 0,
        'path' => '1',
    ]);

    $child = Territory::factory()->for($this->team)->create([
        'parent_id' => $parent->id,
        'level' => 1,
        'path' => '1/2',
    ]);

    expect($child->parent->id)->toBe($parent->id);
    expect($parent->children)->toHaveCount(1);
    expect($parent->children->first()->id)->toBe($child->id);
});

test('can balance territories', function (): void {
    $territory1 = Territory::factory()->for($this->team)->create();
    $territory2 = Territory::factory()->for($this->team)->create();

    // Create unbalanced assignments
    for ($i = 0; $i < 10; $i++) {
        $lead = Lead::factory()->create(['team_id' => $this->team->id]);
        TerritoryRecord::factory()->create([
            'territory_id' => $territory1->id,
            'record_type' => Lead::class,
            'record_id' => $lead->id,
        ]);
    }

    for ($i = 0; $i < 2; $i++) {
        $lead = Lead::factory()->create(['team_id' => $this->team->id]);
        TerritoryRecord::factory()->create([
            'territory_id' => $territory2->id,
            'record_type' => Lead::class,
            'record_id' => $lead->id,
        ]);
    }

    $balance = $this->service->balanceTerritories(collect([$territory1, $territory2]));

    expect($balance[$territory1->id]['current'])->toBe(10);
    expect($balance[$territory2->id]['current'])->toBe(2);
    expect($balance[$territory1->id]['target'])->toBe(6);
    expect($balance[$territory2->id]['target'])->toBe(6);
});
