<?php

declare(strict_types=1);

use App\Models\Note;
use App\Models\Opportunity;
use App\Models\SupportCase;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\Activity\ActivityFeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = resolve(ActivityFeedService::class);
});

it('combines multiple models into paginated results', function (): void {
    $team = Team::factory()->create();

    Task::factory()->count(15)->create(['team_id' => $team->id]);
    Note::factory()->count(10)->create(['team_id' => $team->id]);

    $results = $this->service->getTeamActivity($team->id, perPage: 10);

    expect($results)->toHaveCount(10);
    expect($results->total())->toBe(25);
    expect($results->lastPage())->toBe(3);
});

it('respects team isolation in union queries', function (): void {
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    Task::factory()->count(5)->create(['team_id' => $team1->id]);
    Task::factory()->count(3)->create(['team_id' => $team2->id]);
    Note::factory()->count(2)->create(['team_id' => $team1->id]);

    $results = $this->service->getTeamActivity($team1->id);

    expect($results->total())->toBe(7);
});

it('orders activity by created_at descending', function (): void {
    $team = Team::factory()->create();

    $oldTask = Task::factory()->create([
        'team_id' => $team->id,
        'created_at' => now()->subDays(5),
    ]);

    $newNote = Note::factory()->create([
        'team_id' => $team->id,
        'created_at' => now(),
    ]);

    $results = $this->service->getTeamActivity($team->id);

    expect($results->first()->id)->toBe($newNote->id);
    expect($results->first()->activity_type)->toBe('note');
});

it('includes all activity types in team feed', function (): void {
    $team = Team::factory()->create();

    Task::factory()->create(['team_id' => $team->id]);
    Note::factory()->create(['team_id' => $team->id]);
    Opportunity::factory()->create(['team_id' => $team->id]);
    SupportCase::factory()->create(['team_id' => $team->id]);

    $results = $this->service->getTeamActivity($team->id);

    expect($results->total())->toBe(4);

    $types = $results->pluck('activity_type')->unique()->sort()->values();
    expect($types)->toEqual(['case', 'note', 'opportunity', 'task']);
});

it('gets user activity correctly', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    Task::factory()->count(3)->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
    ]);

    Note::factory()->count(2)->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
    ]);

    $results = $this->service->getUserActivity($user->id);

    expect($results->total())->toBe(5);
});

it('caches team activity results', function (): void {
    $team = Team::factory()->create();

    Task::factory()->count(5)->create(['team_id' => $team->id]);

    // First call - should cache
    $results1 = $this->service->getCachedTeamActivity($team->id, page: 1);

    // Add more tasks
    Task::factory()->count(3)->create(['team_id' => $team->id]);

    // Second call - should return cached results
    $results2 = $this->service->getCachedTeamActivity($team->id, page: 1);

    expect($results1->total())->toBe(5);
    expect($results2->total())->toBe(5); // Still 5 due to cache
});

it('clears team activity cache', function (): void {
    $team = Team::factory()->create();

    Task::factory()->count(5)->create(['team_id' => $team->id]);

    // Cache results
    $this->service->getCachedTeamActivity($team->id, page: 1);

    // Clear cache
    $this->service->clearTeamActivityCache($team->id);

    // Add more tasks
    Task::factory()->count(3)->create(['team_id' => $team->id]);

    // Should get fresh results
    $results = $this->service->getCachedTeamActivity($team->id, page: 1);

    expect($results->total())->toBe(8);
});

it('gets record-specific activity', function (): void {
    $team = Team::factory()->create();
    $company = \App\Models\Company::factory()->create(['team_id' => $team->id]);

    Task::factory()->count(3)->create([
        'team_id' => $team->id,
        'company_id' => $company->id,
    ]);

    Note::factory()->count(2)->create([
        'team_id' => $team->id,
        'notable_type' => \App\Models\Company::class,
        'notable_id' => $company->id,
    ]);

    $results = $this->service->getRecordActivity('Company', $company->id);

    expect($results->total())->toBe(5);
});

it('respects per page parameter', function (): void {
    $team = Team::factory()->create();

    Task::factory()->count(30)->create(['team_id' => $team->id]);

    $results = $this->service->getTeamActivity($team->id, perPage: 15);

    expect($results)->toHaveCount(15);
    expect($results->perPage())->toBe(15);
});

it('uses default per page when not specified', function (): void {
    $team = Team::factory()->create();

    Task::factory()->count(30)->create(['team_id' => $team->id]);

    $results = $this->service->getTeamActivity($team->id);

    expect($results->perPage())->toBe(25); // Default from service
});

it('includes required columns in results', function (): void {
    $team = Team::factory()->create();

    Task::factory()->create(['team_id' => $team->id]);

    $results = $this->service->getTeamActivity($team->id);
    $record = $results->first();

    expect($record)->toHaveKeys([
        'id',
        'name',
        'description',
        'created_at',
        'updated_at',
        'creator_id',
        'activity_type',
        'icon',
        'color',
        'url',
    ]);
});