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
 * **Feature: tasks-activities-enhancement, Property 28: Task completion percentage calculation**
 * **Validates: Requirements 21.1, 21.2, 21.3**
 *
 * Property: Parent task percent_complete is the average of subtasks and updates when subtasks change.
 */
test('property: parent percent complete follows subtask averages', function (): void {
    runPropertyTest(function (): void {
        $parent = generateTask($this->team, $this->user, [
            'percent_complete' => 0,
        ]);

        $subtaskCount = fake()->numberBetween(2, 5);
        $percentages = [];

        for ($i = 0; $i < $subtaskCount; $i++) {
            $percent = fake()->randomFloat(2, 0, 100);
            $percentages[] = $percent;
            generateTask($this->team, $this->user, [
                'parent_id' => $parent->id,
                'percent_complete' => $percent,
            ]);
        }

        $parent->updatePercentComplete();

        $expected = round(array_sum($percentages) / count($percentages), 2);
        $freshParent = $parent->fresh();

        expect((float) $freshParent->percent_complete)->toBe($expected);

        // Update a subtask and ensure parent recalculates
        $child = $freshParent->subtasks()->first();
        $child->update(['percent_complete' => 100]);
        $freshParent->updatePercentComplete();

        $updatedAverage = $freshParent->subtasks()->get()->avg('percent_complete');
        expect((float) $freshParent->fresh()->percent_complete)->toBe(round($updatedAverage, 2));
    }, 25);
})->group('property');

/**
 * **Feature: tasks-activities-enhancement, Property 29: Task completion sets percentage to 100**
 * **Validates: Requirements 21.4**
 *
 * Property: Marking a task as completed forces percent_complete to 100 regardless of previous value.
 */
test('property: completing a task sets percent complete to one hundred', function (): void {
    runPropertyTest(function (): void {
        $task = generateTask($this->team, $this->user, [
            'percent_complete' => fake()->randomFloat(2, 0, 80),
        ]);

        $statusField = CustomField::query()
            ->where('code', TaskField::STATUS->value)
            ->where('entity_type', Task::class)
            ->first();

        expect($statusField)->not->toBeNull();

        $completedOption = $statusField?->options()->where('name', 'Completed')->first();
        expect($completedOption)->not->toBeNull();

        $task->saveCustomFieldValue($statusField, $completedOption->id);
        $task->refresh();

        expect((float) $task->percent_complete)->toBe(100.0);
    }, 25);
})->group('property');
