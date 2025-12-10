<?php

declare(strict_types=1);

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

test('project can be created with basic attributes', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();

    $project = Project::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'Test Project',
        'status' => ProjectStatus::PLANNING,
    ]);

    expect($project->name)->toBe('Test Project')
        ->and($project->status)->toBe(ProjectStatus::PLANNING)
        ->and($project->team_id)->toBe($team->id)
        ->and($project->creator_id)->toBe($user->id)
        ->and($project->slug)->not->toBeNull();
});

test('slug is generated and stays unique when omitted', function (): void {
    $team = Team::factory()->create();

    $first = Project::create([
        'name' => 'Project Alpha',
        'team_id' => $team->id,
    ]);

    $second = Project::create([
        'name' => 'Project Alpha',
        'team_id' => $team->id,
    ]);

    expect($first->slug)->not->toBeNull()
        ->and($second->slug)->not->toBeNull()
        ->and($first->slug)->not->toBe($second->slug);
});

test('project can have team members', function (): void {
    $project = Project::factory()->create();
    $user = User::factory()->create();

    $project->teamMembers()->attach($user->id, [
        'role' => 'Developer',
        'allocation_percentage' => 50,
    ]);

    expect($project->teamMembers)->toHaveCount(1)
        ->and($project->teamMembers->first()->id)->toBe($user->id)
        ->and($project->teamMembers->first()->pivot->role)->toBe('Developer')
        ->and((float) $project->teamMembers->first()->pivot->allocation_percentage)->toBe(50.0);
});

test('project can have tasks', function (): void {
    $project = Project::factory()->create();
    $task = Task::factory()->create(['team_id' => $project->team_id]);

    $project->tasks()->attach($task->id);

    expect($project->tasks)->toHaveCount(1)
        ->and($project->tasks->first()->id)->toBe($task->id);
});

test('project calculates percent complete based on empty task list', function (): void {
    $project = Project::factory()->create();

    $percentComplete = $project->calculatePercentComplete();

    // No tasks = 0%
    expect($percentComplete)->toBe(0.0);
});

test('project can check if over budget', function (): void {
    $project = Project::factory()->create([
        'budget' => 10000,
        'actual_cost' => 12000,
    ]);

    expect($project->isOverBudget())->toBeTrue();

    $project->actual_cost = 8000;
    expect($project->isOverBudget())->toBeFalse();
});

test('project calculates budget variance', function (): void {
    $project = Project::factory()->create([
        'budget' => 10000,
        'actual_cost' => 7000,
    ]);

    expect($project->budgetVariance())->toBe(3000.0);

    $project->actual_cost = 12000;
    expect($project->budgetVariance())->toBe(-2000.0);
});

test('project can be marked as template', function (): void {
    $project = Project::factory()->template()->create();

    expect($project->is_template)->toBeTrue()
        ->and($project->status)->toBe(ProjectStatus::PLANNING);
});

test('project can export data for gantt chart', function (): void {
    $project = Project::factory()->create([
        'name' => 'Test Project',
        'start_date' => now(),
        'end_date' => now()->addMonths(2),
        'percent_complete' => 25,
    ]);

    $ganttData = $project->exportForGantt();

    expect($ganttData)->toHaveKeys(['id', 'name', 'start', 'end', 'progress', 'tasks'])
        ->and($ganttData['name'])->toBe('Test Project')
        ->and((float) $ganttData['progress'])->toBe(25.0);
});

test('cannot create project from non-template', function (): void {
    $project = Project::factory()->create(['is_template' => false]);

    expect(fn () => $project->createFromTemplate('New Project'))
        ->toThrow(\DomainException::class, 'Cannot create project from non-template.');
});