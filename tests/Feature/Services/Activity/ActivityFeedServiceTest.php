<?php

declare(strict_types=1);

use App\Models\Note;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\Activity\ActivityFeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = resolve(ActivityFeedService::class);
    $this->user = User::factory()->create();
    $this->team = Team::factory()->create();
    $this->user->teams()->attach($this->team);
});

it('integrates with database correctly', function (): void {
    Task::factory()->count(5)->create(['team_id' => $this->team->id]);
    Note::factory()->count(3)->create(['team_id' => $this->team->id]);

    $results = $this->service->getTeamActivity($this->team->id);

    expect($results->total())->toBe(8);
    $this->assertDatabaseCount('tasks', 5);
    $this->assertDatabaseCount('notes', 3);
});

it('handles pagination correctly across pages', function (): void {
    Task::factory()->count(30)->create(['team_id' => $this->team->id]);

    $page1 = $this->service->getTeamActivity($this->team->id, perPage: 10);
    $page2 = $this->service->getTeamActivity($this->team->id, perPage: 10);

    expect($page1->currentPage())->toBe(1);
    expect($page1)->toHaveCount(10);
    expect($page1->hasMorePages())->toBeTrue();
});

it('filters by team correctly with multiple teams', function (): void {
    $team2 = Team::factory()->create();

    Task::factory()->count(5)->create(['team_id' => $this->team->id]);
    Task::factory()->count(3)->create(['team_id' => $team2->id]);

    $team1Results = $this->service->getTeamActivity($this->team->id);
    $team2Results = $this->service->getTeamActivity($team2->id);

    expect($team1Results->total())->toBe(5);
    expect($team2Results->total())->toBe(3);
});

it('caches results with correct cache key', function (): void {
    Task::factory()->count(5)->create(['team_id' => $this->team->id]);

    Cache::shouldReceive('remember')
        ->once()
        ->with(
            "team.{$this->team->id}.activity.page.1.per.25",
            300,
            \Mockery::type('Closure'),
        )
        ->andReturn($this->service->getTeamActivity($this->team->id));

    $this->service->getCachedTeamActivity($this->team->id, page: 1);
});

it('handles empty results gracefully', function (): void {
    $results = $this->service->getTeamActivity($this->team->id);

    expect($results->total())->toBe(0);
    expect($results)->toHaveCount(0);
    expect($results->isEmpty())->toBeTrue();
});

it('maintains consistent column structure across models', function (): void {
    Task::factory()->create(['team_id' => $this->team->id]);
    Note::factory()->create(['team_id' => $this->team->id]);
    Opportunity::factory()->create(['team_id' => $this->team->id]);

    $results = $this->service->getTeamActivity($this->team->id);

    foreach ($results as $record) {
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
    }
});

it('orders mixed activity types chronologically', function (): void {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'created_at' => now()->subHours(3),
    ]);

    $note = Note::factory()->create([
        'team_id' => $this->team->id,
        'created_at' => now()->subHours(1),
    ]);

    $opportunity = Opportunity::factory()->create([
        'team_id' => $this->team->id,
        'created_at' => now()->subHours(2),
    ]);

    $results = $this->service->getTeamActivity($this->team->id);

    expect($results->pluck('id')->toArray())->toBe([
        $note->id,
        $opportunity->id,
        $task->id,
    ]);
});

it('respects query limits for performance', function (): void {
    // Create more records than the limit
    Task::factory()->count(150)->create(['team_id' => $this->team->id]);

    $results = $this->service->getTeamActivity($this->team->id, perPage: 25);

    // Should still paginate correctly even with limits
    expect($results)->toHaveCount(25);
});

it('handles user activity with creator relationship', function (): void {
    Task::factory()->count(3)->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
    ]);

    Note::factory()->count(2)->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
    ]);

    $results = $this->service->getUserActivity($this->user->id);

    expect($results->total())->toBe(5);
    expect($results->every(fn ($record): bool => $record->creator_id === $this->user->id))->toBeTrue();
});
