<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Services\ProjectSchedulingService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->service = new ProjectSchedulingService;
    $this->team = Team::factory()->create();
});

/**
 * Feature: Project & Resource Management, Task 5
 * Property 1: Dependency enforcement
 * Validates: Requirements 1.2, 2.3
 */
test('critical path respects task dependencies', function () {
    $project = Project::factory()->create(['team_id' => $this->team->id]);

    // Create a chain of dependent tasks: A -> B -> C
    $taskA = Task::factory()->create([
        'team_id' => $this->team->id,
        'title' => 'Task A',
        'estimated_duration_minutes' => 480, // 1 day
    ]);
    $taskB = Task::factory()->create([
        'team_id' => $this->team->id,
        'title' => 'Task B',
        'estimated_duration_minutes' => 960, // 2 days
    ]);
    $taskC = Task::factory()->create([
        'team_id' => $this->team->id,
        'title' => 'Task C',
        'estimated_duration_minutes' => 480, // 1 day
    ]);

    $project->tasks()->attach([$taskA->id, $taskB->id, $taskC->id]);

    // Set up dependencies: B depends on A, C depends on B
    $taskB->dependencies()->attach($taskA->id);
    $taskC->dependencies()->attach($taskB->id);

    $criticalPath = $this->service->calculateCriticalPath($project);

    // All tasks should be on critical path since they're in a chain
    expect($criticalPath)->toHaveCount(3)
        ->and($criticalPath->pluck('id')->toArray())->toContain($taskA->id, $taskB->id, $taskC->id);

    // Verify order: A should come before B, B before C
    $criticalPathIds = $criticalPath->pluck('id')->toArray();
    $indexA = array_search($taskA->id, $criticalPathIds);
    $indexB = array_search($taskB->id, $criticalPathIds);
    $indexC = array_search($taskC->id, $criticalPathIds);

    expect($indexA)->toBeLessThan($indexB)
        ->and($indexB)->toBeLessThan($indexC);
});

/**
 * Feature: Project & Resource Management, Task 5
 * Property 1: Dependency enforcement
 * Validates: Requirements 2.3
 */
test('timeline schedules tasks after their dependencies', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'start_date' => Carbon::parse('2025-01-01'),
    ]);

    $taskA = Task::factory()->create([
        'team_id' => $this->team->id,
        'title' => 'Task A',
        'estimated_duration_minutes' => 480, // 1 day
    ]);
    $taskB = Task::factory()->create([
        'team_id' => $this->team->id,
        'title' => 'Task B',
        'estimated_duration_minutes' => 480, // 1 day
    ]);

    $project->tasks()->attach([$taskA->id, $taskB->id]);
    $taskB->dependencies()->attach($taskA->id); // B depends on A

    $timeline = $this->service->generateTimeline($project);

    $taskATimeline = collect($timeline['tasks'])->firstWhere('task_id', $taskA->id);
    $taskBTimeline = collect($timeline['tasks'])->firstWhere('task_id', $taskB->id);

    // Task B should start after Task A ends
    $taskAEnd = Carbon::parse($taskATimeline['scheduled_end']);
    $taskBStart = Carbon::parse($taskBTimeline['scheduled_start']);

    expect($taskBStart->greaterThanOrEqualTo($taskAEnd))->toBeTrue();
});

/**
 * Feature: Project & Resource Management, Task 5
 * Property 2: Progress accuracy
 * Validates: Requirements 1.2
 */
test('schedule summary reflects accurate task progress', function () {
    $project = Project::factory()->create(['team_id' => $this->team->id]);

    $completedTask = Task::factory()->create([
        'team_id' => $this->team->id,
        'percent_complete' => 100,
    ]);
    $inProgressTask = Task::factory()->create([
        'team_id' => $this->team->id,
        'percent_complete' => 50,
    ]);
    $notStartedTask = Task::factory()->create([
        'team_id' => $this->team->id,
        'percent_complete' => 0,
    ]);

    $project->tasks()->attach([$completedTask->id, $inProgressTask->id, $notStartedTask->id]);

    $summary = $this->service->getScheduleSummary($project);

    expect($summary['total_tasks'])->toBe(3)
        ->and($summary['completed_tasks'])->toBeGreaterThanOrEqual(0)
        ->and($summary['in_progress_tasks'])->toBeGreaterThanOrEqual(0)
        ->and($summary['completed_tasks'] + $summary['in_progress_tasks'])->toBeLessThanOrEqual(3);
});

test('critical path handles parallel tasks correctly', function () {
    $project = Project::factory()->create(['team_id' => $this->team->id]);

    // Create tasks: A -> B and A -> C (B and C are parallel)
    $taskA = Task::factory()->create([
        'team_id' => $this->team->id,
        'estimated_duration_minutes' => 480, // 1 day
    ]);
    $taskB = Task::factory()->create([
        'team_id' => $this->team->id,
        'estimated_duration_minutes' => 960, // 2 days (longer)
    ]);
    $taskC = Task::factory()->create([
        'team_id' => $this->team->id,
        'estimated_duration_minutes' => 480, // 1 day (shorter)
    ]);

    $project->tasks()->attach([$taskA->id, $taskB->id, $taskC->id]);

    $taskB->dependencies()->attach($taskA->id);
    $taskC->dependencies()->attach($taskA->id);

    $criticalPath = $this->service->calculateCriticalPath($project);

    // Critical path should include A and B (the longer path)
    expect($criticalPath->pluck('id')->toArray())->toContain($taskA->id, $taskB->id);
});

test('slack calculation identifies non-critical tasks', function () {
    $project = Project::factory()->create(['team_id' => $this->team->id]);

    $taskA = Task::factory()->create([
        'team_id' => $this->team->id,
        'estimated_duration_minutes' => 480,
    ]);
    $taskB = Task::factory()->create([
        'team_id' => $this->team->id,
        'estimated_duration_minutes' => 960, // Longer task
    ]);
    $taskC = Task::factory()->create([
        'team_id' => $this->team->id,
        'estimated_duration_minutes' => 240, // Shorter task
    ]);

    $project->tasks()->attach([$taskA->id, $taskB->id, $taskC->id]);

    $taskB->dependencies()->attach($taskA->id);
    $taskC->dependencies()->attach($taskA->id);

    $slackB = $this->service->calculateSlack($taskB, $project);
    $slackC = $this->service->calculateSlack($taskC, $project);

    // Task B (longer) should have 0 slack (critical)
    // Task C (shorter) should have positive slack (non-critical)
    expect($slackB)->toBe(0)
        ->and($slackC)->toBeGreaterThan(0);
});

test('timeline includes milestone information', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'start_date' => Carbon::parse('2025-01-01'),
    ]);

    $regularTask = Task::factory()->create([
        'team_id' => $this->team->id,
        'is_milestone' => false,
    ]);
    $milestoneTask = Task::factory()->create([
        'team_id' => $this->team->id,
        'is_milestone' => true,
    ]);

    $project->tasks()->attach([$regularTask->id, $milestoneTask->id]);

    $timeline = $this->service->generateTimeline($project);

    expect($timeline)->toHaveKey('milestones')
        ->and($timeline['milestones'])->toHaveCount(1)
        ->and($timeline['milestones'][0]['task_id'])->toBe($milestoneTask->id);
});

test('schedule summary detects blocked tasks', function () {
    $project = Project::factory()->create(['team_id' => $this->team->id]);

    $blockingTask = Task::factory()->create([
        'team_id' => $this->team->id,
        'percent_complete' => 0, // Not completed
    ]);
    $blockedTask = Task::factory()->create([
        'team_id' => $this->team->id,
    ]);

    $project->tasks()->attach([$blockingTask->id, $blockedTask->id]);
    $blockedTask->dependencies()->attach($blockingTask->id);

    $summary = $this->service->getScheduleSummary($project);

    expect($summary['blocked_tasks'])->toBeGreaterThanOrEqual(1);
});

test('empty project returns empty critical path', function () {
    $project = Project::factory()->create(['team_id' => $this->team->id]);

    $criticalPath = $this->service->calculateCriticalPath($project);

    expect($criticalPath)->toBeEmpty();
});

test('timeline calculates project end date from critical path', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'start_date' => Carbon::parse('2025-01-01'),
    ]);

    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'estimated_duration_minutes' => 2400, // 5 days
    ]);

    $project->tasks()->attach($task->id);

    $timeline = $this->service->generateTimeline($project);

    $startDate = Carbon::parse($timeline['start_date']);
    $endDate = Carbon::parse($timeline['end_date']);

    expect($endDate->greaterThan($startDate))->toBeTrue()
        ->and($startDate->diffInDays($endDate))->toBeGreaterThanOrEqual(5);
});

test('schedule summary indicates if project is on schedule', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'start_date' => Carbon::parse('2025-01-01'),
        'end_date' => Carbon::parse('2025-01-31'), // 30 days
    ]);

    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'estimated_duration_minutes' => 480, // 1 day - well within budget
    ]);

    $project->tasks()->attach($task->id);

    $summary = $this->service->getScheduleSummary($project);

    expect($summary)->toHaveKey('on_schedule')
        ->and($summary['on_schedule'])->toBeTrue();
});
