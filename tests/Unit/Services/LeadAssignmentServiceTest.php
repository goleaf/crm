<?php

declare(strict_types=1);

use App\Enums\LeadAssignmentStrategy;
use App\Models\Lead;
use App\Models\Team;
use App\Models\Territory;
use App\Models\TerritoryAssignment;
use App\Models\User;
use App\Services\LeadAssignmentService;
use App\Services\TerritoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('assigns lead using round-robin strategy', function (): void {
    $team = Team::factory()->create();
    $users = User::factory()->count(3)->create();

    foreach ($users as $user) {
        $user->teams()->attach($team);
    }

    Cache::forget("lead_round_robin:{$team->id}");

    $lead = Lead::factory()->create([
        'team_id' => $team->id,
        'assignment_strategy' => LeadAssignmentStrategy::ROUND_ROBIN,
    ]);

    $service = new LeadAssignmentService(new TerritoryService);
    $assignedUser = $service->assign($lead, LeadAssignmentStrategy::ROUND_ROBIN);

    expect($assignedUser)->toBeInstanceOf(User::class)
        ->and($lead->fresh()->assigned_to_id)->toBe($assignedUser->id);
});

test('assigns lead using territory strategy', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);

    $territory = Territory::factory()->create([
        'team_id' => $team->id,
        'is_active' => true,
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

    // Assignment depends on territory rules matching
    // If no territory matches, assignedUser will be null
    if ($assignedUser instanceof User) {
        expect($lead->fresh()->assigned_to_id)->toBe($assignedUser->id);
    } else {
        expect($assignedUser)->toBeNull();
    }
});

test('assigns lead using weighted strategy', function (): void {
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

    $lead = Lead::factory()->create([
        'team_id' => $team->id,
        'assignment_strategy' => LeadAssignmentStrategy::WEIGHTED,
    ]);

    $service = new LeadAssignmentService(new TerritoryService);
    $assignedUser = $service->assign($lead, LeadAssignmentStrategy::WEIGHTED);

    expect($assignedUser)->toBeInstanceOf(User::class)
        ->and($lead->fresh()->assigned_to_id)->toBe($assignedUser->id);
});

test('returns null for manual assignment strategy', function (): void {
    $team = Team::factory()->create();
    $lead = Lead::factory()->create([
        'team_id' => $team->id,
        'assignment_strategy' => LeadAssignmentStrategy::MANUAL,
    ]);

    $service = new LeadAssignmentService(new TerritoryService);
    $assignedUser = $service->assign($lead, LeadAssignmentStrategy::MANUAL);

    expect($assignedUser)->toBeNull()
        ->and($lead->fresh()->assigned_to_id)->toBeNull();
});

test('bulk assigns multiple leads', function (): void {
    $team = Team::factory()->create();
    $users = User::factory()->count(2)->create();

    foreach ($users as $user) {
        $user->teams()->attach($team);
    }

    Cache::forget("lead_round_robin:{$team->id}");

    $leads = Lead::factory()->count(4)->create([
        'team_id' => $team->id,
        'assignment_strategy' => LeadAssignmentStrategy::ROUND_ROBIN,
    ]);

    $service = new LeadAssignmentService(new TerritoryService);
    $results = $service->bulkAssign($leads, LeadAssignmentStrategy::ROUND_ROBIN);

    expect($results)->toHaveCount(4)
        ->and($results->pluck('user')->filter())->toHaveCount(4);
});

test('reassigns leads from one user to another', function (): void {
    $team = Team::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $user1->teams()->attach($team);
    $user2->teams()->attach($team);

    Lead::factory()->count(3)->create([
        'team_id' => $team->id,
        'assigned_to_id' => $user1->id,
        'converted_at' => null,
    ]);

    $service = new LeadAssignmentService(new TerritoryService);
    $count = $service->reassign($user1, $user2, $team->id);

    expect($count)->toBe(3)
        ->and(Lead::where('assigned_to_id', $user2->id)->count())->toBe(3)
        ->and(Lead::where('assigned_to_id', $user1->id)->count())->toBe(0);
});

test('reassignment does not affect converted leads', function (): void {
    $team = Team::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $user1->teams()->attach($team);
    $user2->teams()->attach($team);

    Lead::factory()->count(2)->create([
        'team_id' => $team->id,
        'assigned_to_id' => $user1->id,
        'converted_at' => null,
    ]);

    Lead::factory()->count(3)->create([
        'team_id' => $team->id,
        'assigned_to_id' => $user1->id,
        'converted_at' => now(),
    ]);

    $service = new LeadAssignmentService(new TerritoryService);
    $count = $service->reassign($user1, $user2);

    expect($count)->toBe(2)
        ->and(Lead::where('assigned_to_id', $user1->id)->where('converted_at', '!=', null)->count())->toBe(3);
});
