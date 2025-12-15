<?php

declare(strict_types=1);

use App\Enums\CustomFields\TaskField;
use App\Enums\CustomFieldType;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Feature: tasks-activities-enhancement, Property 4: Custom field validation
 * Validates: Requirements 3.1, 4.1, 4.2
 *
 * Property: For any custom field (status, priority, due date, etc.) and value,
 * attempting to set an invalid value should be rejected, and setting a valid
 * value should succeed.
 */
beforeEach(function (): void {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();
    $this->user->teams()->attach($this->team);
    $this->user->switchTeam($this->team);

    $this->statusField = createCustomFieldFor(
        Task::class,
        TaskField::STATUS->value,
        CustomFieldType::SELECT->value,
        ['Not Started', 'In Progress', 'Completed'],
        $this->team,
    );

    $this->priorityField = createCustomFieldFor(
        Task::class,
        TaskField::PRIORITY->value,
        CustomFieldType::SELECT->value,
        ['Low', 'Medium', 'High'],
        $this->team,
    );

    $this->dueDateField = createCustomFieldFor(
        Task::class,
        TaskField::DUE_DATE->value,
        CustomFieldType::DATE_TIME->value,
        [],
        $this->team,
    );
});

test('task status field accepts valid values and rejects invalid values', function (): void {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Test Task',
    ]);

    // Get a valid status option
    $validOption = $this->statusField->options()->first();
    expect($validOption)->not->toBeNull();

    // Setting a valid status should succeed
    $task->saveCustomFieldValue($this->statusField, $validOption->id);
    $savedValue = $task->getCustomFieldValue($this->statusField);
    expect($savedValue)->toBe($validOption->id);

    expect(fn (): null => $task->saveCustomFieldValue($this->statusField, 999999))
        ->toThrow(\InvalidArgumentException::class);
});

test('task priority field accepts valid values and rejects invalid values', function (): void {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Test Task',
    ]);

    // Get a valid priority option
    $validOption = $this->priorityField->options()->first();
    expect($validOption)->not->toBeNull();

    // Setting a valid priority should succeed
    $task->saveCustomFieldValue($this->priorityField, $validOption->id);
    $savedValue = $task->getCustomFieldValue($this->priorityField);
    expect($savedValue)->toBe($validOption->id);

    expect(fn (): null => $task->saveCustomFieldValue($this->priorityField, 999999))
        ->toThrow(\InvalidArgumentException::class);
});

test('task due date field accepts valid dates and rejects invalid dates', function (): void {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Test Task',
    ]);

    // Setting a valid date should succeed
    $validDate = now()->addDays(7);
    $task->saveCustomFieldValue($this->dueDateField, $validDate);
    $savedValue = $task->getCustomFieldValue($this->dueDateField);

    // The saved value should be a date
    expect($savedValue)->toBeInstanceOf(\DateTimeInterface::class);
    expect($savedValue->format('Y-m-d'))->toBe($validDate->format('Y-m-d'));

    expect(fn (): null => $task->saveCustomFieldValue($this->dueDateField, 'not-a-date'))
        ->toThrow(\InvalidArgumentException::class);
});

test('task custom fields maintain data integrity across multiple updates', function (): void {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Test Task',
    ]);

    // Get valid options
    $statusOption1 = $this->statusField->options()->first();
    $statusOption2 = $this->statusField->options()->skip(1)->first();
    $priorityOption1 = $this->priorityField->options()->first();
    $priorityOption2 = $this->priorityField->options()->skip(1)->first();

    // Set initial values
    $task->saveCustomFieldValue($this->statusField, $statusOption1->id);
    $task->saveCustomFieldValue($this->priorityField, $priorityOption1->id);
    $task->refresh();

    // Verify initial values
    expect($task->getCustomFieldValue($this->statusField))->toBe($statusOption1->id);
    expect($task->getCustomFieldValue($this->priorityField))->toBe($priorityOption1->id);

    // Update status without affecting priority
    $task->saveCustomFieldValue($this->statusField, $statusOption2->id);
    $task->refresh();

    expect($task->getCustomFieldValue($this->statusField))->toBe($statusOption2->id);
    expect($task->getCustomFieldValue($this->priorityField))->toBe($priorityOption1->id);

    // Update priority without affecting status
    $task->saveCustomFieldValue($this->priorityField, $priorityOption2->id);
    $task->refresh();

    expect($task->getCustomFieldValue($this->statusField))->toBe($statusOption2->id);
    expect($task->getCustomFieldValue($this->priorityField))->toBe($priorityOption2->id);
});

test('task custom field validation respects field type constraints', function (): void {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Test Task',
    ]);

    // Get the due date field (should be date type)
    // Setting a date object should work
    $validDate = now()->addWeek();
    $task->saveCustomFieldValue($this->dueDateField, $validDate);
    $task->refresh();
    $savedValue = $task->getCustomFieldValue($this->dueDateField);
    expect($savedValue)->toBeInstanceOf(\DateTimeInterface::class);

    // Setting a valid date string should work
    $dateString = '2025-12-31';
    $task->saveCustomFieldValue($this->dueDateField, $dateString);
    $task->refresh();
    $savedValue = $task->getCustomFieldValue($this->dueDateField);
    expect($savedValue)->toBeInstanceOf(\DateTimeInterface::class);

    // Get a select field (status or priority)
    // Setting a valid option ID should work
    $validOption = $this->statusField->options()->first();
    $task->saveCustomFieldValue($this->statusField, $validOption->id);
    $task->refresh();
    $savedValue = $task->getCustomFieldValue($this->statusField);
    expect($savedValue)->toBe($validOption->id);

    // Setting a string value for a select field should be handled appropriately
    expect(fn (): null => $task->saveCustomFieldValue($this->statusField, 'some-string-value'))
        ->toThrow(\InvalidArgumentException::class);
});
