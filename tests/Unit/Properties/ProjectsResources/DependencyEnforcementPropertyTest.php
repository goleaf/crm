<?php

declare(strict_types=1);

namespace Tests\Unit\Properties\ProjectsResources;

use App\Models\Project;
use App\Models\Task;
use App\Services\ProjectSchedulingService;
use Tests\Support\Generators\ProjectGenerator;
use Tests\Support\Generators\TaskGenerator;
use Tests\Support\PropertyTestCase;

/**
 * Feature: projects-resources, Property 1: Dependency enforcement
 * Validates: Requirements 2.1, 2.3
 *
 * Property: Task start/end dates respect predecessor relationships;
 * critical path reflects dependencies and durations.
 */
final class DependencyEnforcementPropertyTest extends PropertyTestCase
{
    private ProjectSchedulingService $schedulingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schedulingService = resolve(ProjectSchedulingService::class);
    }

    /**
     * @test
     */
    public function task_scheduled_dates_respect_predecessor_relationships(): void
    {
        $this->runPropertyTest(function (): void {
            // Generate a project with tasks
            $project = ProjectGenerator::generate($this->team, $this->user);

            // Create tasks with dependencies
            $taskA = TaskGenerator::generate($this->team, $this->user, [
                'estimated_duration_minutes' => fake()->numberBetween(60, 480),
            ]);
            $taskB = TaskGenerator::generate($this->team, $this->user, [
                'estimated_duration_minutes' => fake()->numberBetween(60, 480),
            ]);
            $taskC = TaskGenerator::generate($this->team, $this->user, [
                'estimated_duration_minutes' => fake()->numberBetween(60, 480),
            ]);

            $project->tasks()->attach([$taskA->id, $taskB->id, $taskC->id]);

            // B depends on A, C depends on B
            $taskB->dependencies()->attach($taskA->id);
            $taskC->dependencies()->attach($taskB->id);

            // Generate timeline
            $timeline = $this->schedulingService->generateTimeline($project);

            // Find scheduled dates for each task
            $taskASchedule = collect($timeline['tasks'])->firstWhere('task_id', $taskA->id);
            $taskBSchedule = collect($timeline['tasks'])->firstWhere('task_id', $taskB->id);
            $taskCSchedule = collect($timeline['tasks'])->firstWhere('task_id', $taskC->id);

            // Property: Task B must start after Task A finishes
            $this->assertGreaterThanOrEqual(
                \Illuminate\Support\Facades\Date::parse($taskASchedule['scheduled_end']),
                \Illuminate\Support\Facades\Date::parse($taskBSchedule['scheduled_start']),
                'Task B must start on or after Task A finishes',
            );

            // Property: Task C must start after Task B finishes
            $this->assertGreaterThanOrEqual(
                \Illuminate\Support\Facades\Date::parse($taskBSchedule['scheduled_end']),
                \Illuminate\Support\Facades\Date::parse($taskCSchedule['scheduled_start']),
                'Task C must start on or after Task B finishes',
            );
        }, 100);
    }

    /**
     * @test
     */
    public function critical_path_reflects_longest_dependency_chain(): void
    {
        $this->runPropertyTest(function (): void {
            // Generate a project with multiple task chains
            $project = ProjectGenerator::generate($this->team, $this->user);

            // Create two parallel chains: A->B->C (longer) and D->E (shorter)
            $taskA = TaskGenerator::generate($this->team, $this->user, [
                'estimated_duration_minutes' => 480, // 1 day
            ]);
            $taskB = TaskGenerator::generate($this->team, $this->user, [
                'estimated_duration_minutes' => 480, // 1 day
            ]);
            $taskC = TaskGenerator::generate($this->team, $this->user, [
                'estimated_duration_minutes' => 480, // 1 day
            ]);
            $taskD = TaskGenerator::generate($this->team, $this->user, [
                'estimated_duration_minutes' => 240, // 0.5 day
            ]);
            $taskE = TaskGenerator::generate($this->team, $this->user, [
                'estimated_duration_minutes' => 240, // 0.5 day
            ]);

            $project->tasks()->attach([$taskA->id, $taskB->id, $taskC->id, $taskD->id, $taskE->id]);

            // Chain 1: A->B->C
            $taskB->dependencies()->attach($taskA->id);
            $taskC->dependencies()->attach($taskB->id);

            // Chain 2: D->E
            $taskE->dependencies()->attach($taskD->id);

            // Calculate critical path
            $criticalPath = $this->schedulingService->calculateCriticalPath($project);

            // Property: Critical path should contain the longer chain (A, B, C)
            $criticalTaskIds = $criticalPath->pluck('id')->toArray();

            $this->assertContains($taskA->id, $criticalTaskIds, 'Task A should be on critical path');
            $this->assertContains($taskB->id, $criticalTaskIds, 'Task B should be on critical path');
            $this->assertContains($taskC->id, $criticalTaskIds, 'Task C should be on critical path');

            // Property: Critical path tasks should have zero slack
            $timeline = $this->schedulingService->generateTimeline($project);
            foreach ($criticalPath as $task) {
                $taskSchedule = collect($timeline['tasks'])->firstWhere('task_id', $task->id);
                $this->assertEquals(0, $taskSchedule['slack_days'], "Critical task {$task->id} should have zero slack");
            }
        }, 100);
    }

    /**
     * @test
     */
    public function critical_path_length_equals_project_duration(): void
    {
        $this->runPropertyTest(function (): void {
            $project = ProjectGenerator::generate($this->team, $this->user);

            // Create a simple chain of tasks
            $taskCount = fake()->numberBetween(3, 6);
            $tasks = [];
            $totalDuration = 0;

            for ($i = 0; $i < $taskCount; $i++) {
                $duration = fake()->numberBetween(1, 3) * 480; // 1-3 days in minutes
                $totalDuration += ceil($duration / 480); // Convert to days

                $task = TaskGenerator::generate($this->team, $this->user, [
                    'estimated_duration_minutes' => $duration,
                ]);
                $tasks[] = $task;

                $project->tasks()->attach($task->id);

                // Create dependency chain
                if ($i > 0) {
                    $task->dependencies()->attach($tasks[$i - 1]->id);
                }
            }

            // Calculate critical path
            $criticalPath = $this->schedulingService->calculateCriticalPath($project);
            $timeline = $this->schedulingService->generateTimeline($project);

            // Property: All tasks should be on critical path (single chain)
            $this->assertCount($taskCount, $criticalPath, 'All tasks in chain should be critical');

            // Property: Project duration should equal sum of task durations
            $projectStart = \Illuminate\Support\Facades\Date::parse($timeline['start_date']);
            $projectEnd = \Illuminate\Support\Facades\Date::parse($timeline['end_date']);
            $actualDuration = $projectStart->diffInDays($projectEnd);

            $this->assertEquals(
                $totalDuration,
                $actualDuration,
                'Project duration should equal sum of critical path task durations',
            );
        }, 100);
    }
}
