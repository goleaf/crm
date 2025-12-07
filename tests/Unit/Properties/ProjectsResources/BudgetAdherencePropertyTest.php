<?php

declare(strict_types=1);

namespace Tests\Unit\Properties\ProjectsResources;

use App\Models\Project;
use App\Models\TaskTimeEntry;
use Illuminate\Support\Carbon;
use Tests\Support\Generators\ProjectGenerator;
use Tests\Support\Generators\TaskGenerator;
use Tests\Support\PropertyTestCase;

/**
 * Feature: projects-resources, Property 4: Budget adherence
 * Validates: Requirements 4.1, 4.2
 *
 * Property: Project budget vs actuals updates from time logs/billing
 * and reports overruns.
 */
final class BudgetAdherencePropertyTest extends PropertyTestCase
{
    /**
     * @test
     */
    public function project_actual_cost_equals_sum_of_billable_time_entries(): void
    {
        $this->runPropertyTest(function (): void {
            $project = ProjectGenerator::generate($this->team, $this->user, [
                'budget' => fake()->randomFloat(2, 5000, 50000),
            ]);

            // Create tasks with time entries
            $taskCount = fake()->numberBetween(2, 5);
            $expectedTotal = 0;

            for ($i = 0; $i < $taskCount; $i++) {
                $task = TaskGenerator::generate($this->team, $this->user);
                $project->tasks()->attach($task->id);

                // Add random time entries
                $entryCount = fake()->numberBetween(1, 3);
                for ($j = 0; $j < $entryCount; $j++) {
                    $duration = fake()->numberBetween(30, 480); // 30 min to 8 hours
                    $rate = fake()->randomFloat(2, 50, 200);
                    $isBillable = fake()->boolean(80); // 80% billable

                    TaskTimeEntry::factory()->create([
                        'task_id' => $task->id,
                        'user_id' => $this->user->id,
                        'duration_minutes' => $duration,
                        'is_billable' => $isBillable,
                        'billing_rate' => $isBillable ? $rate : null,
                        'started_at' => Carbon::now()->subHours(2),
                        'ended_at' => Carbon::now()->subHours(2)->addMinutes($duration),
                    ]);

                    if ($isBillable) {
                        $expectedTotal += ($duration / 60) * $rate;
                    }
                }
            }

            // Property: Actual cost should equal sum of billable time
            $project->updateActualCost();
            $actualCost = round($project->actual_cost, 2);
            $expectedCost = round($expectedTotal, 2);

            $this->assertEquals(
                $expectedCost,
                $actualCost,
                'Project actual cost should equal sum of billable time entries'
            );
        }, 100);
    }

    /**
     * @test
     */
    public function project_is_over_budget_when_actual_exceeds_budget(): void
    {
        $this->runPropertyTest(function (): void {
            $budget = fake()->randomFloat(2, 1000, 5000);
            $project = ProjectGenerator::generate($this->team, $this->user, [
                'budget' => $budget,
            ]);

            $task = TaskGenerator::generate($this->team, $this->user);
            $project->tasks()->attach($task->id);

            // Create time entries that exceed budget
            $rate = 100;
            $hoursNeeded = ($budget / $rate) + fake()->numberBetween(1, 10); // Exceed budget
            $minutes = (int) ($hoursNeeded * 60);

            TaskTimeEntry::factory()->create([
                'task_id' => $task->id,
                'user_id' => $this->user->id,
                'duration_minutes' => $minutes,
                'is_billable' => true,
                'billing_rate' => $rate,
                'started_at' => Carbon::now()->subHours(2),
                'ended_at' => Carbon::now()->subHours(2)->addMinutes($minutes),
            ]);

            // Property: Project should be flagged as over budget
            $project->updateActualCost();

            $this->assertTrue(
                $project->isOverBudget(),
                'Project should be over budget when actual cost exceeds budget'
            );

            // Property: Budget variance should be negative
            $this->assertLessThan(
                0,
                $project->budgetVariance(),
                'Budget variance should be negative when over budget'
            );
        }, 100);
    }

    /**
     * @test
     */
    public function budget_variance_is_budget_minus_actual_cost(): void
    {
        $this->runPropertyTest(function (): void {
            $budget = fake()->randomFloat(2, 5000, 10000);
            $project = ProjectGenerator::generate($this->team, $this->user, [
                'budget' => $budget,
            ]);

            $task = TaskGenerator::generate($this->team, $this->user);
            $project->tasks()->attach($task->id);

            // Create time entries
            $rate = 100;
            $hours = fake()->numberBetween(10, 40);
            $minutes = $hours * 60;
            $expectedCost = $hours * $rate;

            TaskTimeEntry::factory()->create([
                'task_id' => $task->id,
                'user_id' => $this->user->id,
                'duration_minutes' => $minutes,
                'is_billable' => true,
                'billing_rate' => $rate,
                'started_at' => Carbon::now()->subHours(2),
                'ended_at' => Carbon::now()->subHours(2)->addMinutes($minutes),
            ]);

            // Property: Budget variance = budget - actual cost
            $project->updateActualCost();
            $expectedVariance = round($budget - $expectedCost, 2);
            $actualVariance = round($project->budgetVariance(), 2);

            $this->assertEquals(
                $expectedVariance,
                $actualVariance,
                'Budget variance should equal budget minus actual cost'
            );
        }, 100);
    }

    /**
     * @test
     */
    public function budget_utilization_is_actual_cost_divided_by_budget(): void
    {
        $this->runPropertyTest(function (): void {
            $budget = fake()->randomFloat(2, 5000, 10000);
            $project = ProjectGenerator::generate($this->team, $this->user, [
                'budget' => $budget,
            ]);

            $task = TaskGenerator::generate($this->team, $this->user);
            $project->tasks()->attach($task->id);

            // Create time entries
            $rate = 100;
            $hours = fake()->numberBetween(10, 40);
            $minutes = $hours * 60;
            $expectedCost = $hours * $rate;

            TaskTimeEntry::factory()->create([
                'task_id' => $task->id,
                'user_id' => $this->user->id,
                'duration_minutes' => $minutes,
                'is_billable' => true,
                'billing_rate' => $rate,
                'started_at' => Carbon::now()->subHours(2),
                'ended_at' => Carbon::now()->subHours(2)->addMinutes($minutes),
            ]);

            // Property: Budget utilization = (actual cost / budget) * 100
            $project->updateActualCost();
            $expectedUtilization = round(($expectedCost / $budget) * 100, 2);
            $actualUtilization = $project->budgetUtilization();

            $this->assertEquals(
                $expectedUtilization,
                $actualUtilization,
                'Budget utilization should equal (actual cost / budget) * 100'
            );
        }, 100);
    }

    /**
     * @test
     */
    public function non_billable_time_does_not_affect_actual_cost(): void
    {
        $this->runPropertyTest(function (): void {
            $project = ProjectGenerator::generate($this->team, $this->user, [
                'budget' => fake()->randomFloat(2, 5000, 10000),
            ]);

            $task = TaskGenerator::generate($this->team, $this->user);
            $project->tasks()->attach($task->id);

            // Create non-billable time entries
            $entryCount = fake()->numberBetween(2, 5);
            for ($i = 0; $i < $entryCount; $i++) {
                $duration = fake()->numberBetween(60, 480);

                TaskTimeEntry::factory()->create([
                    'task_id' => $task->id,
                    'user_id' => $this->user->id,
                    'duration_minutes' => $duration,
                    'is_billable' => false,
                    'billing_rate' => null,
                    'started_at' => Carbon::now()->subHours(2),
                    'ended_at' => Carbon::now()->subHours(2)->addMinutes($duration),
                ]);
            }

            // Property: Actual cost should be 0 for non-billable time
            $project->updateActualCost();

            $this->assertEquals(
                0,
                $project->actual_cost,
                'Non-billable time should not contribute to actual cost'
            );
        }, 100);
    }
}
