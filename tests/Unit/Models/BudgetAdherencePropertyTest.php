<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskTimeEntry;
use App\Models\Team;
use App\Models\User;

/**
 * Feature: projects-resources, Property 4: Budget adherence
 *
 * Property: Project budget vs actuals updates from time logs/billing and reports overruns.
 *
 * For any project with time entries, the actual cost should equal the sum of all billable
 * time entries across all tasks, and budget variance/utilization should be calculated correctly.
 */
test('property: budget adherence - actual cost equals sum of billable time entries', function (): void {
    // Run 100 iterations to test various scenarios
    for ($i = 0; $i < 100; $i++) {
        $team = Team::factory()->create();
        $user = User::factory()->create();

        // Create project with random budget
        $budget = fake()->randomFloat(2, 10000, 100000);
        $project = Project::factory()->create([
            'team_id' => $team->id,
            'budget' => $budget,
            'currency' => 'USD',
        ]);

        // Create random number of tasks (1-5)
        $taskCount = fake()->numberBetween(1, 5);
        $expectedTotal = 0;

        for ($t = 0; $t < $taskCount; $t++) {
            $task = Task::factory()->create([
                'team_id' => $team->id,
            ]);

            $project->tasks()->attach($task->id);

            // Create random number of time entries per task (0-3)
            $entryCount = fake()->numberBetween(0, 3);

            for ($e = 0; $e < $entryCount; $e++) {
                $durationMinutes = fake()->numberBetween(30, 480); // 30 min to 8 hours
                $billingRate = fake()->randomFloat(2, 50, 200);
                $isBillable = fake()->boolean(80); // 80% chance of being billable

                TaskTimeEntry::factory()->create([
                    'task_id' => $task->id,
                    'user_id' => $user->id,
                    'duration_minutes' => $durationMinutes,
                    'is_billable' => $isBillable,
                    'billing_rate' => $isBillable ? $billingRate : null,
                    'started_at' => now()->subHours($e + 1),
                    'ended_at' => now()->subHours($e + 1)->addMinutes($durationMinutes),
                ]);

                if ($isBillable) {
                    $expectedTotal += ($durationMinutes / 60) * $billingRate;
                }
            }
        }

        // Update actual cost
        $project->updateActualCost();

        // Property: actual cost should equal sum of billable time entries
        expect(round($project->actual_cost, 2))->toBe(round($expectedTotal, 2));

        // Property: budget variance should be budget - actual_cost
        if ($project->budget !== null) {
            $expectedVariance = $budget - $project->actual_cost;
            expect(round($project->budgetVariance(), 2))->toBe(round($expectedVariance, 2));
        }

        // Property: budget utilization should be (actual_cost / budget) * 100
        if ($project->budget !== null && $project->budget > 0) {
            $expectedUtilization = ($project->actual_cost / $budget) * 100;
            expect(round($project->budgetUtilization(), 2))->toBe(round($expectedUtilization, 2));
        }

        // Property: isOverBudget should be true when actual_cost > budget
        if ($project->budget !== null) {
            $shouldBeOverBudget = $project->actual_cost > $budget;
            expect($project->isOverBudget())->toBe($shouldBeOverBudget);
        }

        // Clean up for next iteration
        $project->tasks()->detach();
        $project->delete();
        Task::whereIn('id', $project->tasks->pluck('id'))->delete();
        TaskTimeEntry::where('user_id', $user->id)->delete();
        $user->delete();
        $team->delete();
    }
});

test('property: budget summary provides accurate task breakdown', function (): void {
    // Run 50 iterations
    for ($i = 0; $i < 50; $i++) {
        $team = Team::factory()->create();
        $user = User::factory()->create();

        $project = Project::factory()->create([
            'team_id' => $team->id,
            'budget' => fake()->randomFloat(2, 10000, 50000),
        ]);

        $taskCount = fake()->numberBetween(2, 4);
        $expectedTotalMinutes = 0;
        $expectedTotalAmount = 0;

        for ($t = 0; $t < $taskCount; $t++) {
            $task = Task::factory()->create([
                'team_id' => $team->id,
            ]);

            $project->tasks()->attach($task->id);

            $entryCount = fake()->numberBetween(1, 3);

            for ($e = 0; $e < $entryCount; $e++) {
                $durationMinutes = fake()->numberBetween(60, 240);
                $billingRate = fake()->randomFloat(2, 75, 150);

                TaskTimeEntry::factory()->create([
                    'task_id' => $task->id,
                    'user_id' => $user->id,
                    'duration_minutes' => $durationMinutes,
                    'is_billable' => true,
                    'billing_rate' => $billingRate,
                    'started_at' => now()->subHours($e + 1),
                    'ended_at' => now()->subHours($e + 1)->addMinutes($durationMinutes),
                ]);

                $expectedTotalMinutes += $durationMinutes;
                $expectedTotalAmount += ($durationMinutes / 60) * $billingRate;
            }
        }

        $project->updateActualCost();
        $summary = $project->getBudgetSummary();

        // Property: summary should contain all required keys
        expect($summary)->toHaveKeys([
            'project_id',
            'project_name',
            'currency',
            'budget',
            'actual_cost',
            'variance',
            'utilization_percentage',
            'is_over_budget',
            'task_breakdown',
            'total_billable_minutes',
            'total_billable_hours',
        ]);

        // Property: task breakdown should have entry for each task
        expect($summary['task_breakdown'])->toHaveCount($taskCount);

        // Property: total billable minutes should match sum of all entries
        expect($summary['total_billable_minutes'])->toBe($expectedTotalMinutes);

        // Property: total billable hours should be minutes / 60
        expect(round($summary['total_billable_hours'], 2))->toBe(round($expectedTotalMinutes / 60, 2));

        // Property: actual cost in summary should match project actual_cost
        expect(round($summary['actual_cost'], 2))->toBe(round($project->actual_cost, 2));

        // Clean up
        $project->tasks()->detach();
        $project->delete();
        Task::whereIn('id', $project->tasks->pluck('id'))->delete();
        TaskTimeEntry::where('user_id', $user->id)->delete();
        $user->delete();
        $team->delete();
    }
});

test('property: time log export contains all required fields', function (): void {
    // Run 30 iterations
    for ($i = 0; $i < 30; $i++) {
        $team = Team::factory()->create();
        $user = User::factory()->create();

        $project = Project::factory()->create([
            'team_id' => $team->id,
        ]);

        $task = Task::factory()->create([
            'team_id' => $team->id,
        ]);

        $project->tasks()->attach($task->id);

        $entryCount = fake()->numberBetween(1, 5);

        for ($e = 0; $e < $entryCount; $e++) {
            $durationMinutes = fake()->numberBetween(30, 300);
            $isBillable = fake()->boolean();
            $billingRate = $isBillable ? fake()->randomFloat(2, 50, 200) : null;

            TaskTimeEntry::factory()->create([
                'task_id' => $task->id,
                'user_id' => $user->id,
                'duration_minutes' => $durationMinutes,
                'is_billable' => $isBillable,
                'billing_rate' => $billingRate,
                'started_at' => now()->subHours($e + 1),
                'ended_at' => now()->subHours($e + 1)->addMinutes($durationMinutes),
                'note' => fake()->sentence(),
            ]);
        }

        $export = $project->exportTimeLogs();

        // Property: export should have entry for each time log
        expect($export)->toHaveCount($entryCount);

        // Property: each exported entry should have all required fields
        foreach ($export as $entry) {
            expect($entry)->toHaveKeys([
                'task_id',
                'task_name',
                'user_id',
                'user_name',
                'started_at',
                'ended_at',
                'duration_minutes',
                'duration_hours',
                'is_billable',
                'billing_rate',
                'billing_amount',
                'note',
            ]);

            // Property: duration_hours should be duration_minutes / 60
            expect(round($entry['duration_hours'], 2))->toBe(round($entry['duration_minutes'] / 60, 2));

            // Property: billing_amount should be calculated correctly
            if ($entry['is_billable'] && $entry['billing_rate'] !== null) {
                $expectedAmount = round(($entry['duration_minutes'] / 60) * $entry['billing_rate'], 2);
                expect(round($entry['billing_amount'], 2))->toBe($expectedAmount);
            } else {
                expect($entry['billing_amount'])->toBe(0);
            }
        }

        // Clean up
        $project->tasks()->detach();
        $project->delete();
        $task->delete();
        TaskTimeEntry::where('user_id', $user->id)->delete();
        $user->delete();
        $team->delete();
    }
});
