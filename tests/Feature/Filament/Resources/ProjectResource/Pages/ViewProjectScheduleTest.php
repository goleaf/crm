<?php

declare(strict_types=1);

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\ViewProjectSchedule;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->team = Team::factory()->create();
    $this->user->teams()->attach($this->team);
    $this->actingAs($this->user);
});

describe('ViewProjectSchedule Page', function (): void {
    it('can render the page', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Test Project',
        ]);

        livewire(ViewProjectSchedule::class, ['record' => $project->id])
            ->assertSuccessful();
    });

    it('displays project schedule title', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
        ]);

        livewire(ViewProjectSchedule::class, ['record' => $project->id])
            ->assertSee(__('app.labels.project_schedule'));
    });

    it('can access the page from project resource', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
        ]);

        $this->get(ProjectResource::getUrl('schedule', ['record' => $project]))
            ->assertSuccessful();
    });
});

describe('Gantt Chart Data', function (): void {
    it('displays gantt chart export section', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'start_date' => \Illuminate\Support\Facades\Date::parse('2025-01-01'),
        ]);

        livewire(ViewProjectSchedule::class, ['record' => $project->id])
            ->assertSee(__('app.labels.gantt_chart_data'))
            ->assertSee(__('app.actions.export_json'));
    });

    it('provides gantt data with project details', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Export Test Project',
            'start_date' => \Illuminate\Support\Facades\Date::parse('2025-01-01'),
            'percent_complete' => 50,
        ]);

        $task = Task::factory()->create([
            'team_id' => $this->team->id,
            'title' => 'Test Task',
            'estimated_duration_minutes' => 480,
        ]);

        $project->tasks()->attach($task->id);

        $component = livewire(ViewProjectSchedule::class, ['record' => $project->id]);

        $ganttData = $component->get('ganttData');

        expect($ganttData)
            ->toHaveKey('id', $project->id)
            ->toHaveKey('name', 'Export Test Project')
            ->toHaveKey('progress', 50)
            ->toHaveKey('tasks')
            ->toHaveKey('milestones')
            ->toHaveKey('critical_path');
    });

    it('includes task details in gantt data', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'start_date' => \Illuminate\Support\Facades\Date::parse('2025-01-01'),
        ]);

        $task = Task::factory()->create([
            'team_id' => $this->team->id,
            'title' => 'Design Phase',
            'estimated_duration_minutes' => 960,
            'percent_complete' => 75,
            'is_milestone' => false,
        ]);

        $project->tasks()->attach($task->id);

        $component = livewire(ViewProjectSchedule::class, ['record' => $project->id]);
        $ganttData = $component->get('ganttData');

        expect($ganttData['tasks'])
            ->toHaveCount(1)
            ->and($ganttData['tasks'][0])
            ->toHaveKey('id', $task->id)
            ->toHaveKey('name', 'Design Phase')
            ->toHaveKey('progress', 75)
            ->toHaveKey('is_milestone', false);
    });

    it('identifies critical path tasks in gantt data', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'start_date' => \Illuminate\Support\Facades\Date::parse('2025-01-01'),
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

        $component = livewire(ViewProjectSchedule::class, ['record' => $project->id]);
        $ganttData = $component->get('ganttData');

        expect($ganttData['critical_path'])
            ->toContain($taskA->id, $taskB->id);
    });

    it('includes milestones in gantt data', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'start_date' => \Illuminate\Support\Facades\Date::parse('2025-01-01'),
        ]);

        $milestone = Task::factory()->create([
            'team_id' => $this->team->id,
            'title' => 'Phase 1 Complete',
            'is_milestone' => true,
        ]);

        $project->tasks()->attach($milestone->id);

        $component = livewire(ViewProjectSchedule::class, ['record' => $project->id]);
        $ganttData = $component->get('ganttData');

        expect($ganttData['milestones'])->toHaveCount(1);
    });
});

describe('Budget Summary', function (): void {
    it('displays budget summary section', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'budget' => 10000,
        ]);

        livewire(ViewProjectSchedule::class, ['record' => $project->id])
            ->assertSee(__('app.labels.budget_summary'))
            ->assertSee(__('app.labels.budget'))
            ->assertSee(__('app.labels.actual_cost'))
            ->assertSee(__('app.labels.variance'))
            ->assertSee(__('app.labels.utilization'));
    });

    it('shows budget details correctly', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'budget' => 10000,
            'actual_cost' => 7500,
            'currency' => 'USD',
        ]);

        $component = livewire(ViewProjectSchedule::class, ['record' => $project->id]);
        $budgetSummary = $component->get('budgetSummary');

        expect($budgetSummary)
            ->toHaveKey('budget', 10000)
            ->toHaveKey('actual_cost', 7500)
            ->toHaveKey('currency', 'USD')
            ->toHaveKey('variance', 2500)
            ->toHaveKey('utilization_percentage', 75.0)
            ->toHaveKey('is_over_budget', false);
    });

    it('indicates when project is over budget', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'budget' => 5000,
            'actual_cost' => 6000,
        ]);

        $component = livewire(ViewProjectSchedule::class, ['record' => $project->id]);
        $budgetSummary = $component->get('budgetSummary');

        expect($budgetSummary['is_over_budget'])->toBeTrue()
            ->and($budgetSummary['variance'])->toBe(-1000.0)
            ->and($budgetSummary['utilization_percentage'])->toBe(120.0);
    });

    it('handles projects without budget', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'budget' => null,
            'actual_cost' => 5000,
        ]);

        $component = livewire(ViewProjectSchedule::class, ['record' => $project->id]);
        $budgetSummary = $component->get('budgetSummary');

        expect($budgetSummary['budget'])->toBeNull()
            ->and($budgetSummary['variance'])->toBeNull()
            ->and($budgetSummary['utilization_percentage'])->toBeNull()
            ->and($budgetSummary['is_over_budget'])->toBeFalse();
    });

    it('displays task breakdown in budget summary', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'budget' => 10000,
        ]);

        $task = Task::factory()->create([
            'team_id' => $this->team->id,
            'title' => 'Development Task',
        ]);

        $project->tasks()->attach($task->id);

        $component = livewire(ViewProjectSchedule::class, ['record' => $project->id]);
        $budgetSummary = $component->get('budgetSummary');

        expect($budgetSummary)
            ->toHaveKey('task_breakdown')
            ->and($budgetSummary['task_breakdown'])
            ->toBeArray();
    });

    it('calculates total billable hours correctly', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
        ]);

        $component = livewire(ViewProjectSchedule::class, ['record' => $project->id]);
        $budgetSummary = $component->get('budgetSummary');

        expect($budgetSummary)
            ->toHaveKey('total_billable_minutes')
            ->toHaveKey('total_billable_hours');
    });
});

describe('Project Schedule Widget', function (): void {
    it('includes project schedule widget in header', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
        ]);

        $component = livewire(ViewProjectSchedule::class, ['record' => $project->id]);

        $widgets = $component->instance()->getHeaderWidgets();

        expect($widgets)->toHaveCount(1);
    });

    it('passes project to widget', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Widget Test Project',
        ]);

        $component = livewire(ViewProjectSchedule::class, ['record' => $project->id]);
        $widgets = $component->instance()->getHeaderWidgets();

        expect($widgets[0]->project->id)->toBe($project->id);
    });

    it('widget returns correct view data structure', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'start_date' => \Illuminate\Support\Facades\Date::parse('2025-01-01'),
        ]);

        $task = Task::factory()->create([
            'team_id' => $this->team->id,
            'estimated_duration_minutes' => 480,
        ]);

        $project->tasks()->attach($task->id);

        $component = livewire(ViewProjectSchedule::class, ['record' => $project->id]);
        $widgets = $component->instance()->getHeaderWidgets();
        $widget = $widgets[0];

        $viewData = $widget->getViewData();

        expect($viewData)
            ->toHaveKey('summary')
            ->toHaveKey('criticalPath')
            ->toHaveKey('timeline')
            ->toHaveKey('project')
            ->and($viewData['project']->id)->toBe($project->id);
    });
});

describe('Authorization', function (): void {
    it('requires authentication', function (): void {
        auth()->logout();

        $project = Project::factory()->create([
            'team_id' => $this->team->id,
        ]);

        $this->get(ProjectResource::getUrl('schedule', ['record' => $project]))
            ->assertRedirect();
    });

    it('respects team boundaries', function (): void {
        $otherTeam = Team::factory()->create();
        $project = Project::factory()->create([
            'team_id' => $otherTeam->id,
        ]);

        livewire(ViewProjectSchedule::class, ['record' => $project->id])
            ->assertForbidden();
    });
});

describe('Edge Cases', function (): void {
    it('handles project with no tasks', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
        ]);

        $component = livewire(ViewProjectSchedule::class, ['record' => $project->id]);

        $ganttData = $component->get('ganttData');

        expect($ganttData['tasks'])->toBeEmpty()
            ->and($ganttData['milestones'])->toBeEmpty()
            ->and($ganttData['critical_path'])->toBeEmpty();
    });

    it('handles project with no start date', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'start_date' => null,
        ]);

        livewire(ViewProjectSchedule::class, ['record' => $project->id])
            ->assertSuccessful();
    });

    it('handles zero budget project', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'budget' => 0,
            'actual_cost' => 0,
        ]);

        $component = livewire(ViewProjectSchedule::class, ['record' => $project->id]);
        $budgetSummary = $component->get('budgetSummary');

        expect($budgetSummary['utilization_percentage'])->toBeNull();
    });

    it('handles complex task dependencies', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'start_date' => \Illuminate\Support\Facades\Date::parse('2025-01-01'),
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

        livewire(ViewProjectSchedule::class, ['record' => $project->id])
            ->assertSuccessful();
    });

    it('handles project with both budget and no budget tasks', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'budget' => 10000,
        ]);

        $task1 = Task::factory()->create([
            'team_id' => $this->team->id,
            'title' => 'Billable Task',
        ]);

        $task2 = Task::factory()->create([
            'team_id' => $this->team->id,
            'title' => 'Non-billable Task',
        ]);

        $project->tasks()->attach([$task1->id, $task2->id]);

        $component = livewire(ViewProjectSchedule::class, ['record' => $project->id]);
        $budgetSummary = $component->get('budgetSummary');

        expect($budgetSummary['task_breakdown'])->toHaveCount(2);
    });

    it('returns correct view data structure', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'start_date' => \Illuminate\Support\Facades\Date::parse('2025-01-01'),
        ]);

        $component = livewire(ViewProjectSchedule::class, ['record' => $project->id]);
        $viewData = $component->instance()->getViewData();

        expect($viewData)
            ->toHaveKey('project')
            ->toHaveKey('ganttData')
            ->toHaveKey('budgetSummary')
            ->and($viewData['project']->id)->toBe($project->id)
            ->and($viewData['ganttData'])->toBeArray()
            ->and($viewData['budgetSummary'])->toBeArray();
    });

    it('page title is translatable', function (): void {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
        ]);

        $component = livewire(ViewProjectSchedule::class, ['record' => $project->id]);
        $title = $component->instance()->getTitle();

        expect($title)->toBe(__('app.labels.project_schedule'));
    });
});
