<?php

declare(strict_types=1);

use App\Enums\CustomFields\TaskField;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Relaticle\CustomFields\Models\CustomField;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();
    $this->user->teams()->attach($this->team);
    actingAs($this->user);
});

/**
 * **Feature: tasks-activities-enhancement, Property 31: Milestone task management**
 * **Validates: Requirements 23.1, 23.3, 23.5**
 *
 * Property: Milestones can be toggled, scoped, and completion status aggregates correctly.
 */
test('property: milestones toggle and report completion correctly', function (): void {
    runPropertyTest(function (): void {
        $statusField = CustomField::query()
            ->where('code', TaskField::STATUS->value)
            ->where('entity_type', Task::class)
            ->first();

        $completedOption = $statusField?->options()->where('name', 'Completed')->first();

        expect($statusField)->not->toBeNull();
        expect($completedOption)->not->toBeNull();

        $tasks = collect();
        $milestoneIds = collect();
        $completedMilestoneIds = collect();

        $count = fake()->numberBetween(3, 6);

        for ($i = 0; $i < $count; $i++) {
            $task = generateTask($this->team, $this->user, ['is_milestone' => false]);

            if (fake()->boolean()) {
                $task->markAsMilestone();
                $milestoneIds->push($task->id);

                if (fake()->boolean()) {
                    $task->saveCustomFieldValue($statusField, $completedOption->id);
                    $completedMilestoneIds->push($task->id);
                }
            }

            $tasks->push($task->fresh());
        }

        // Scope should return only milestones
        $scoped = Task::query()->milestones()->pluck('id');
        foreach ($milestoneIds as $id) {
            expect($scoped->contains($id))->toBeTrue();
        }

        // Unmark a milestone and ensure scope drops it
        if ($milestoneIds->isNotEmpty()) {
            $taskToUnmark = Task::find($milestoneIds->first());
            $taskToUnmark?->unmarkAsMilestone();
            $scopedAfter = Task::query()->milestones()->pluck('id');
            expect($scopedAfter->contains($taskToUnmark?->id))->toBeFalse();
        }

        $freshTasks = Task::query()
            ->whereIn('id', $tasks->pluck('id'))
            ->get();

        $status = Task::getMilestoneCompletionStatus($freshTasks);

        $expectedTotal = Task::query()->where('is_milestone', true)->count();
        $expectedCompleted = Task::query()
            ->where('is_milestone', true)
            ->get()
            ->filter(fn (Task $task): bool => $task->isCompleted())
            ->count();

        expect($status['total'])->toBe($expectedTotal);
        expect($status['completed'])->toBe($expectedCompleted);

        $expectedPercentage = $expectedTotal === 0
            ? 0.0
            : round(($expectedCompleted / $expectedTotal) * 100, 2);
        expect($status['percentage'])->toBe($expectedPercentage);
    }, 25);
})->group('property');
