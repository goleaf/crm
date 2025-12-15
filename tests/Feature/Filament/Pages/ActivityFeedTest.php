<?php

declare(strict_types=1);

use App\Filament\Pages\ActivityFeed;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->team = Team::factory()->create();
    $this->user->teams()->attach($this->team);

    $this->actingAs($this->user);
});

it('can render activity feed page', function (): void {
    livewire(ActivityFeed::class)
        ->assertSuccessful();
});

it('displays activities in table', function (): void {
    $task = Task::factory()->create(['team_id' => $this->team->id]);
    $note = Note::factory()->create(['team_id' => $this->team->id]);

    livewire(ActivityFeed::class)
        ->assertCanSeeTableRecords([$task, $note]);
});

it('can filter activities by type', function (): void {
    Task::factory()->count(5)->create(['team_id' => $this->team->id]);
    Note::factory()->count(3)->create(['team_id' => $this->team->id]);

    livewire(ActivityFeed::class)
        ->filterTable('activity_type', 'task')
        ->assertCountTableRecords(5);
});

it('can search activities', function (): void {
    Task::factory()->create([
        'team_id' => $this->team->id,
        'title' => 'Important Task',
    ]);

    Task::factory()->create([
        'team_id' => $this->team->id,
        'title' => 'Regular Task',
    ]);

    livewire(ActivityFeed::class)
        ->searchTable('Important')
        ->assertCountTableRecords(1);
});

it('can sort activities by created date', function (): void {
    $oldTask = Task::factory()->create([
        'team_id' => $this->team->id,
        'created_at' => now()->subDays(5),
    ]);

    $newTask = Task::factory()->create([
        'team_id' => $this->team->id,
        'created_at' => now(),
    ]);

    livewire(ActivityFeed::class)
        ->sortTable('created_at', 'desc')
        ->assertCanSeeTableRecords([$newTask, $oldTask], inOrder: true);
});

it('respects team isolation', function (): void {
    $team2 = Team::factory()->create();

    Task::factory()->create(['team_id' => $this->team->id]);
    Task::factory()->create(['team_id' => $team2->id]);

    livewire(ActivityFeed::class)
        ->assertCountTableRecords(1);
});

it('can paginate activities', function (): void {
    Task::factory()->count(30)->create(['team_id' => $this->team->id]);

    livewire(ActivityFeed::class)
        ->assertCanSeeTableRecords(Task::limit(15)->get())
        ->assertCountTableRecords(15);
});

it('displays activity type badges', function (): void {
    Task::factory()->create(['team_id' => $this->team->id]);

    livewire(ActivityFeed::class)
        ->assertSee('task');
});

it('can view activity details', function (): void {
    $task = Task::factory()->create(['team_id' => $this->team->id]);

    livewire(ActivityFeed::class)
        ->callTableAction('view', $task);
});

it('filters by date range', function (): void {
    Task::factory()->create([
        'team_id' => $this->team->id,
        'created_at' => now()->subDays(10),
    ]);

    Task::factory()->create([
        'team_id' => $this->team->id,
        'created_at' => now()->subDays(2),
    ]);

    livewire(ActivityFeed::class)
        ->filterTable('created_at', [
            'created_from' => now()->subDays(5)->format('Y-m-d'),
            'created_until' => now()->format('Y-m-d'),
        ])
        ->assertCountTableRecords(1);
});

it('displays creator information', function (): void {
    $creator = User::factory()->create(['name' => 'John Doe']);

    Task::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $creator->id,
    ]);

    livewire(ActivityFeed::class)
        ->assertSee('John Doe');
});

it('handles empty state', function (): void {
    livewire(ActivityFeed::class)
        ->assertCountTableRecords(0);
});

it('can filter by multiple activity types', function (): void {
    Task::factory()->count(3)->create(['team_id' => $this->team->id]);
    Note::factory()->count(2)->create(['team_id' => $this->team->id]);
    Opportunity::factory()->count(1)->create(['team_id' => $this->team->id]);

    livewire(ActivityFeed::class)
        ->filterTable('activity_type', ['task', 'note'])
        ->assertCountTableRecords(5);
});

it('polls for new activities', function (): void {
    livewire(ActivityFeed::class)
        ->assertSet('pollingInterval', '30s');
});

it('displays activity icons', function (): void {
    Task::factory()->create(['team_id' => $this->team->id]);

    livewire(ActivityFeed::class)
        ->assertSee('heroicon-o-check-circle');
});
