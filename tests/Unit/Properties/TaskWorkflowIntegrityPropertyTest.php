<?php

declare(strict_types=1);

use App\Enums\CustomFields\TaskField;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();
    $this->user->teams()->attach($this->team);
    actingAs($this->user);

    // Create status custom field with options
    $this->statusField = createCustomFieldFor(
        Task::class,
        TaskField::STATUS->value,
        'select',
        ['Not Started', 'In Progress', 'Completed'],
        $this->team,
    );

    $this->completedOption = $this->statusField->options->firstWhere('name', 'Completed');
});

/**
 * **Feature: communication-collaboration, Property 4: Task workflow integrity**
 *
 * **Validates: Requirements 5.3**
 *
 * Property: For any task with dependencies, the task cannot be marked as completed
 * until all its dependencies are completed.
 */
test('property: tasks with incomplete dependencies cannot be completed', function (): void {
    runPropertyTest(function (): void {
        // Generate a task with dependencies
        $task = generateTask($this->team, $this->user);
        $dependencyCount = fake()->numberBetween(1, 5);

        $dependencies = [];
        for ($i = 0; $i < $dependencyCount; $i++) {
            $dep = generateTask($this->team, $this->user);
            $dependencies[] = $dep;
        }

        $task->dependencies()->attach($dependencies);

        // Ensure at least one dependency is not completed
        $incompleteDep = fake()->randomElement($dependencies);
        $incompleteDep->percent_complete = fake()->randomFloat(2, 0, 99);
        $incompleteDep->save();

        // Try to mark task as completed
        $blocked = $task->isBlocked();

        // Property: Task should be blocked
        expect($blocked)->toBeTrue(
            "Task {$task->id} should be blocked when dependency {$incompleteDep->id} is incomplete",
        );

        // Property: Attempting to complete should throw exception
        try {
            $task->saveCustomFieldValue($this->statusField, $this->completedOption->id);
            $failed = false;
        } catch (\DomainException $e) {
            $failed = true;
            expect($e->getMessage())->toContain('dependent tasks');
        }

        expect($failed)->toBeTrue('Should throw exception when trying to complete blocked task');
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 4: Task workflow integrity**
 *
 * **Validates: Requirements 5.3**
 *
 * Property: When all dependencies are completed, a task can be marked as completed.
 */
test('property: tasks with all dependencies completed can be completed', function (): void {
    runPropertyTest(function (): void {
        // Generate a task with dependencies
        $task = generateTask($this->team, $this->user);
        $dependencyCount = fake()->numberBetween(1, 5);

        $dependencies = [];
        for ($i = 0; $i < $dependencyCount; $i++) {
            $dep = generateTask($this->team, $this->user);
            // Mark all dependencies as completed
            $dep->percent_complete = 100;
            $dep->save();
            $dep->saveCustomFieldValue($this->statusField, $this->completedOption->id);
            $dependencies[] = $dep;
        }

        $task->dependencies()->attach($dependencies);

        // Task should not be blocked
        $blocked = $task->isBlocked();

        expect($blocked)->toBeFalse(
            "Task {$task->id} should not be blocked when all dependencies are completed",
        );

        // Should be able to complete the task
        $task->saveCustomFieldValue($this->statusField, $this->completedOption->id);

        expect($task->fresh()->isCompleted())->toBeTrue();
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 4: Task workflow integrity**
 *
 * **Validates: Requirements 5.1**
 *
 * Property: When a parent task has subtasks, its percent_complete should be
 * calculated based on the average of its subtasks' percent_complete.
 */
test('property: parent task percent complete is average of subtasks', function (): void {
    runPropertyTest(function (): void {
        // Generate a parent task
        $parent = generateTask($this->team, $this->user);

        // Generate random number of subtasks
        $subtaskCount = fake()->numberBetween(2, 10);
        $subtasks = [];
        $totalProgress = 0;

        for ($i = 0; $i < $subtaskCount; $i++) {
            $subtask = generateTask($this->team, $this->user, [
                'parent_id' => $parent->id,
            ]);

            // Set random progress
            $progress = fake()->randomFloat(2, 0, 100);
            $subtask->percent_complete = $progress;
            $subtask->save();

            $subtasks[] = $subtask;
            $totalProgress += $progress;
        }

        // Calculate expected average
        $expectedAverage = round($totalProgress / $subtaskCount, 2);

        // Update parent's percent complete
        $parent->updatePercentComplete();

        // Property: Parent's percent_complete should equal average of subtasks
        expect((float) $parent->fresh()->percent_complete)->toBe(
            $expectedAverage,
            "Parent task {$parent->id} percent_complete should be {$expectedAverage}, got {$parent->percent_complete}",
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 4: Task workflow integrity**
 *
 * **Validates: Requirements 5.1**
 *
 * Property: When a subtask's progress changes, the parent task's progress should update.
 */
test('property: subtask progress changes propagate to parent', function (): void {
    runPropertyTest(function (): void {
        // Generate a parent task with subtasks
        $parent = generateTask($this->team, $this->user);

        $subtaskCount = fake()->numberBetween(2, 5);
        $subtasks = [];

        for ($i = 0; $i < $subtaskCount; $i++) {
            $subtask = generateTask($this->team, $this->user, [
                'parent_id' => $parent->id,
                'percent_complete' => 0,
            ]);
            $subtasks[] = $subtask;
        }

        // Get initial parent progress
        $parent->updatePercentComplete();
        $initialProgress = $parent->fresh()->percent_complete;

        // Update one subtask's progress
        $subtask = fake()->randomElement($subtasks);
        $newProgress = fake()->randomFloat(2, 50, 100);
        $subtask->percent_complete = $newProgress;
        $subtask->save();
        $subtask->updatePercentComplete(); // This should trigger parent update

        // Calculate expected new average
        $totalProgress = collect($subtasks)->sum(fn ($t) => $t->fresh()->percent_complete);
        $expectedAverage = round($totalProgress / $subtaskCount, 2);

        // Property: Parent progress should have changed
        $newParentProgress = $parent->fresh()->percent_complete;

        expect((float) $newParentProgress)->toBe(
            $expectedAverage,
            'Parent progress should update when subtask changes',
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 4: Task workflow integrity**
 *
 * **Validates: Requirements 5.3**
 *
 * Property: Task start dates must respect dependency constraints.
 */
test('property: task start dates cannot violate dependency constraints', function (): void {
    runPropertyTest(function (): void {
        // Generate two tasks with dependency
        $dependency = generateTask($this->team, $this->user, [
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(5),
        ]);

        $task = generateTask($this->team, $this->user, [
            'start_date' => now()->addDays(3), // Starts before dependency ends
            'end_date' => now()->addDays(10),
        ]);

        $task->dependencies()->attach($dependency);

        // Property: Task should violate dependency constraints
        $violates = $task->violatesDependencyConstraints();

        expect($violates)->toBeTrue(
            "Task {$task->id} starting at {$task->start_date} should violate constraint with dependency ending at {$dependency->end_date}",
        );

        // Property: Earliest start date should be after dependency end
        $earliestStart = $task->getEarliestStartDate();

        expect($earliestStart->greaterThanOrEqualTo($dependency->end_date))->toBeTrue(
            'Earliest start date should be after dependency end date',
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 4: Task workflow integrity**
 *
 * **Validates: Requirements 5.1**
 *
 * Property: Completing a task sets percent_complete to 100.
 */
test('property: completing a task sets percent complete to 100', function (): void {
    runPropertyTest(function (): void {
        // Generate a task with random initial progress
        $task = generateTask($this->team, $this->user, [
            'percent_complete' => fake()->randomFloat(2, 0, 99),
        ]);

        $initialProgress = $task->percent_complete;

        // Mark as completed
        $task->saveCustomFieldValue($this->statusField, $this->completedOption->id);

        // Property: percent_complete should be 100
        expect((float) $task->fresh()->percent_complete)->toBe(
            100.0,
            "Task {$task->id} should have 100% progress when completed, had {$initialProgress}% initially",
        );
    }, 100);
})->group('property');
