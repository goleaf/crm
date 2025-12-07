<?php

declare(strict_types=1);

use App\Filament\Widgets\ProjectScheduleWidget;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = Team::factory()->create();
    $this->user->teams()->attach($this->team);
    $this->actingAs($this->user);
});

describe('ProjectScheduleWidget', function () {
    it('returns empty data when no project is set', function () {
        $widget = new ProjectScheduleWidget;
        $viewData = $widget->getViewData();

        expect($viewData)
            ->toHaveKey('summary', null)
            ->toHaveKey('criticalPath')
            ->toHaveKey('timeline', null)
            ->and($viewData['criticalPath'])->toBeEmpty();
    });

    it('returns schedule summary when project is set', function () {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'start_date' => Carbon::parse('2025-01-01'),
        ]);

        $task = Task::factory()->create([
            'team_id' => $this->team->id,
            'estimated_duration_minutes' => 480,
        ]);

        $project->tasks()->attach($task->id);

        $widget = new ProjectScheduleWidget;
        $widget->project = $project;
        $viewData = $widget->getViewData();

        expect($viewData)
            ->toHaveKey('summary')
            ->toHaveKey('criticalPath')
            ->toHaveKey('timeline')
            ->toHaveKey('project')
            ->and($viewData['summary'])->toBeArray()
            ->and($viewData['summary'])->toHaveKey('total_tasks', 1)
            ->and($viewData['project']->id)->toBe($project->id);
    });

    it('returns critical path tasks', function () {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'start_date' => Carbon::parse('2025-01-01'),
        ]);

        $taskA = Task::factory()->create([
            'team_id' => $this->team->id,
            'estimated_duration_minutes' => 480,
        ]);
        $taskB = Task::factory()->create([
            'team_id' => $this->team->id,
            'estimated_duration_minutes' => 960,
        ]);

        $project->tasks()->attach([$taskA->id, $taskB->id]);
        $taskB->dependencies()->attach($taskA->id);

        $widget = new ProjectScheduleWidget;
        $widget->project = $project;
        $viewData = $widget->getViewData();

        expect($viewData['criticalPath'])
            ->toHaveCount(2)
            ->and($viewData['criticalPath']->pluck('id')->toArray())
            ->toContain($taskA->id, $taskB->id);
    });

    it('returns timeline with milestones', function () {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'start_date' => Carbon::parse('2025-01-01'),
        ]);

        $milestone = Task::factory()->create([
            'team_id' => $this->team->id,
            'is_milestone' => true,
        ]);

        $project->tasks()->attach($milestone->id);

        $widget = new ProjectScheduleWidget;
        $widget->project = $project;
        $viewData = $widget->getViewData();

        expect($viewData['timeline'])
            ->toBeArray()
            ->toHaveKey('milestones')
            ->and($viewData['timeline']['milestones'])->toHaveCount(1);
    });

    it('can be viewed by authenticated users', function () {
        expect(ProjectScheduleWidget::canView())->toBeTrue();
    });

    it('cannot be viewed by unauthenticated users', function () {
        auth()->logout();

        expect(ProjectScheduleWidget::canView())->toBeFalse();
    });

    it('has full column span', function () {
        $widget = new ProjectScheduleWidget;
        $reflection = new ReflectionClass($widget);
        $property = $reflection->getProperty('columnSpan');
        $property->setAccessible(true);

        expect($property->getValue($widget))->toBe('full');
    });

    it('uses correct view', function () {
        $widget = new ProjectScheduleWidget;
        $reflection = new ReflectionClass($widget);
        $property = $reflection->getProperty('view');
        $property->setAccessible(true);

        expect($property->getValue($widget))->toBe('filament.widgets.project-schedule-widget');
    });

    it('handles project with no tasks gracefully', function () {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
        ]);

        $widget = new ProjectScheduleWidget;
        $widget->project = $project;
        $viewData = $widget->getViewData();

        expect($viewData['summary'])
            ->toBeArray()
            ->toHaveKey('total_tasks', 0)
            ->and($viewData['criticalPath'])->toBeEmpty();
    });

    it('handles project with complex dependencies', function () {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'start_date' => Carbon::parse('2025-01-01'),
        ]);

        // Create diamond dependency: A -> B,C -> D
        $taskA = Task::factory()->create(['team_id' => $this->team->id]);
        $taskB = Task::factory()->create(['team_id' => $this->team->id]);
        $taskC = Task::factory()->create(['team_id' => $this->team->id]);
        $taskD = Task::factory()->create(['team_id' => $this->team->id]);

        $project->tasks()->attach([$taskA->id, $taskB->id, $taskC->id, $taskD->id]);

        $taskB->dependencies()->attach($taskA->id);
        $taskC->dependencies()->attach($taskA->id);
        $taskD->dependencies()->attach([$taskB->id, $taskC->id]);

        $widget = new ProjectScheduleWidget;
        $widget->project = $project;
        $viewData = $widget->getViewData();

        expect($viewData['summary']['total_tasks'])->toBe(4)
            ->and($viewData['criticalPath'])->not->toBeEmpty();
    });
});
