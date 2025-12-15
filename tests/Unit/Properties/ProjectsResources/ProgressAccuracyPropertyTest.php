<?php

declare(strict_types=1);

namespace Tests\Unit\Properties\ProjectsResources;

use App\Models\Project;
use App\Models\Task;
use Tests\Support\Generators\ProjectGenerator;
use Tests\Support\Generators\TaskGenerator;
use Tests\Support\PropertyTestCase;

/**
 * Feature: projects-resources, Property 2: Progress accuracy
 * Validates: Requirements 1.2, 2.1
 *
 * Property: Project percent complete rolls up from task percentages
 * weighted by effort/duration.
 */
final class ProgressAccuracyPropertyTest extends PropertyTestCase
{
    /**
     * @test
     */
    public function project_percent_complete_reflects_task_completion(): void
    {
        $this->runPropertyTest(function (): void {
            $project = ProjectGenerator::generate($this->team, $this->user);

            // Create random number of tasks
            $taskCount = fake()->numberBetween(3, 10);
            $completedCount = fake()->numberBetween(0, $taskCount);

            for ($i = 0; $i < $taskCount; $i++) {
                $task = TaskGenerator::generate($this->team, $this->user, [
                    'percent_complete' => $i < $completedCount ? 100 : 0,
                ]);
                $project->tasks()->attach($task->id);
            }

            // Calculate expected percentage
            $expectedPercentage = round(($completedCount / $taskCount) * 100, 2);

            // Update project completion
            $project->updatePercentComplete();

            // Property: Project completion should match task completion ratio
            $this->assertEquals(
                $expectedPercentage,
                $project->percent_complete,
                'Project completion should equal ratio of completed tasks',
            );
        }, 100);
    }

    /**
     * @test
     */
    public function project_completion_is_zero_when_no_tasks_exist(): void
    {
        $this->runPropertyTest(function (): void {
            $project = ProjectGenerator::generate($this->team, $this->user);

            // Property: Project with no tasks should have 0% completion
            $calculatedCompletion = $project->calculatePercentComplete();

            $this->assertEquals(
                0,
                $calculatedCompletion,
                'Project with no tasks should have 0% completion',
            );
        }, 100);
    }

    /**
     * @test
     */
    public function project_completion_is_100_when_all_tasks_complete(): void
    {
        $this->runPropertyTest(function (): void {
            $project = ProjectGenerator::generate($this->team, $this->user);

            // Create tasks all marked as complete
            $taskCount = fake()->numberBetween(3, 10);

            for ($i = 0; $i < $taskCount; $i++) {
                $task = TaskGenerator::generate($this->team, $this->user, [
                    'percent_complete' => 100,
                ]);
                $project->tasks()->attach($task->id);
            }

            // Update project completion
            $project->updatePercentComplete();

            // Property: Project should be 100% complete when all tasks are complete
            $this->assertEquals(
                100,
                $project->percent_complete,
                'Project should be 100% complete when all tasks are complete',
            );
        }, 100);
    }

    /**
     * @test
     */
    public function project_completion_updates_when_task_completion_changes(): void
    {
        $this->runPropertyTest(function (): void {
            $project = ProjectGenerator::generate($this->team, $this->user);

            // Create tasks with initial completion
            $taskCount = fake()->numberBetween(3, 8);
            $tasks = [];

            for ($i = 0; $i < $taskCount; $i++) {
                $task = TaskGenerator::generate($this->team, $this->user, [
                    'percent_complete' => 0,
                ]);
                $project->tasks()->attach($task->id);
                $tasks[] = $task;
            }

            // Initial state: 0% complete
            $project->updatePercentComplete();
            $this->assertEquals(0, $project->percent_complete);

            // Complete some tasks
            $tasksToComplete = fake()->numberBetween(1, $taskCount);
            for ($i = 0; $i < $tasksToComplete; $i++) {
                $tasks[$i]->update(['percent_complete' => 100]);
            }

            // Property: Project completion should update to reflect new task states
            $project->updatePercentComplete();
            $expectedPercentage = round(($tasksToComplete / $taskCount) * 100, 2);

            $this->assertEquals(
                $expectedPercentage,
                $project->percent_complete,
                'Project completion should update when task completion changes',
            );
        }, 100);
    }
}
