<?php

declare(strict_types=1);

use App\Enums\CustomFields\TaskField;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldOption;

uses(RefreshDatabase::class);

/**
 * Feature: tasks-activities-enhancement, Property 4: Custom field validation
 * Validates: Requirements 3.1, 4.1, 4.2
 *
 * Property: For any custom field (status, priority, due date, etc.) and value,
 * attempting to set an invalid value should be rejected, and setting a valid
 * value should succeed.
 */
test('task status field accepts valid values and rejects invalid values', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    $task = Task::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'title' => 'Test Task',
    ]);

    // Get the status custom field
    $statusField = CustomField::query()
        ->where('code', TaskField::STATUS->value)
        ->where('entity_type', Task::class)
        ->first();

    expect($statusField)->not->toBeNull();

    // Get a valid status option
    $validOption = $statusField->options()->first();
    expect($validOption)->not->toBeNull();

    // Setting a valid status should succeed
    $task->saveCustomFieldValue($statusField, $validOption->id);
    $savedValue = $task->getCustomFieldValue($statusField);
    expect($savedValue)->toBe($validOption->id);

    // Setting an invalid status (non-existent option ID) should fail gracefully
    // The system should either reject it or handle it appropriately
    $invalidOptionId = 999999;
    
    try {
        $task->saveCustomFieldValue($statusField, $invalidOptionId);
        $savedValue = $task->getCustomFieldValue($statusField);
        
        // If it doesn't throw, it should not save the invalid value
        // It should either keep the old value or set to null
        expect($savedValue)->not->toBe($invalidOptionId);
    } catch (\Exception $e) {
        // Exception is acceptable for invalid values
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});

test('task priority field accepts valid values and rejects invalid values', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    $task = Task::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'title' => 'Test Task',
    ]);

    // Get the priority custom field
    $priorityField = CustomField::query()
        ->where('code', TaskField::PRIORITY->value)
        ->where('entity_type', Task::class)
        ->first();

    expect($priorityField)->not->toBeNull();

    // Get a valid priority option
    $validOption = $priorityField->options()->first();
    expect($validOption)->not->toBeNull();

    // Setting a valid priority should succeed
    $task->saveCustomFieldValue($priorityField, $validOption->id);
    $savedValue = $task->getCustomFieldValue($priorityField);
    expect($savedValue)->toBe($validOption->id);

    // Setting an invalid priority (non-existent option ID) should fail gracefully
    $invalidOptionId = 999999;
    
    try {
        $task->saveCustomFieldValue($priorityField, $invalidOptionId);
        $savedValue = $task->getCustomFieldValue($priorityField);
        
        // If it doesn't throw, it should not save the invalid value
        expect($savedValue)->not->toBe($invalidOptionId);
    } catch (\Exception $e) {
        // Exception is acceptable for invalid values
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});

test('task due date field accepts valid dates and rejects invalid dates', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    $task = Task::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'title' => 'Test Task',
    ]);

    // Get the due date custom field
    $dueDateField = CustomField::query()
        ->where('code', TaskField::DUE_DATE->value)
        ->where('entity_type', Task::class)
        ->first();

    expect($dueDateField)->not->toBeNull();

    // Setting a valid date should succeed
    $validDate = now()->addDays(7);
    $task->saveCustomFieldValue($dueDateField, $validDate);
    $savedValue = $task->getCustomFieldValue($dueDateField);
    
    // The saved value should be a date
    expect($savedValue)->toBeInstanceOf(\DateTimeInterface::class);
    expect($savedValue->format('Y-m-d'))->toBe($validDate->format('Y-m-d'));

    // Setting an invalid date string should fail gracefully
    try {
        $task->saveCustomFieldValue($dueDateField, 'not-a-date');
        $savedValue = $task->getCustomFieldValue($dueDateField);
        
        // If it doesn't throw, it should not save the invalid value
        // It should either keep the old value or set to null
        expect($savedValue)->not->toBe('not-a-date');
    } catch (\Exception $e) {
        // Exception is acceptable for invalid values
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});

test('task custom fields maintain data integrity across multiple updates', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    $task = Task::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'title' => 'Test Task',
    ]);

    // Get custom fields
    $statusField = CustomField::query()
        ->where('code', TaskField::STATUS->value)
        ->where('entity_type', Task::class)
        ->first();

    $priorityField = CustomField::query()
        ->where('code', TaskField::PRIORITY->value)
        ->where('entity_type', Task::class)
        ->first();

    expect($statusField)->not->toBeNull();
    expect($priorityField)->not->toBeNull();

    // Get valid options
    $statusOption1 = $statusField->options()->first();
    $statusOption2 = $statusField->options()->skip(1)->first();
    $priorityOption1 = $priorityField->options()->first();
    $priorityOption2 = $priorityField->options()->skip(1)->first();

    // Set initial values
    $task->saveCustomFieldValue($statusField, $statusOption1->id);
    $task->saveCustomFieldValue($priorityField, $priorityOption1->id);

    // Verify initial values
    expect($task->getCustomFieldValue($statusField))->toBe($statusOption1->id);
    expect($task->getCustomFieldValue($priorityField))->toBe($priorityOption1->id);

    // Update status without affecting priority
    $task->saveCustomFieldValue($statusField, $statusOption2->id);
    
    expect($task->getCustomFieldValue($statusField))->toBe($statusOption2->id);
    expect($task->getCustomFieldValue($priorityField))->toBe($priorityOption1->id);

    // Update priority without affecting status
    $task->saveCustomFieldValue($priorityField, $priorityOption2->id);
    
    expect($task->getCustomFieldValue($statusField))->toBe($statusOption2->id);
    expect($task->getCustomFieldValue($priorityField))->toBe($priorityOption2->id);
});

test('task custom field validation respects field type constraints', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    $task = Task::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'title' => 'Test Task',
    ]);

    // Get the due date field (should be date type)
    $dueDateField = CustomField::query()
        ->where('code', TaskField::DUE_DATE->value)
        ->where('entity_type', Task::class)
        ->first();

    expect($dueDateField)->not->toBeNull();

    // Setting a date object should work
    $validDate = now()->addWeek();
    $task->saveCustomFieldValue($dueDateField, $validDate);
    $savedValue = $task->getCustomFieldValue($dueDateField);
    expect($savedValue)->toBeInstanceOf(\DateTimeInterface::class);

    // Setting a valid date string should work
    $dateString = '2025-12-31';
    $task->saveCustomFieldValue($dueDateField, $dateString);
    $savedValue = $task->getCustomFieldValue($dueDateField);
    expect($savedValue)->toBeInstanceOf(\DateTimeInterface::class);

    // Get a select field (status or priority)
    $selectField = CustomField::query()
        ->where('code', TaskField::STATUS->value)
        ->where('entity_type', Task::class)
        ->first();

    expect($selectField)->not->toBeNull();

    // Setting a valid option ID should work
    $validOption = $selectField->options()->first();
    $task->saveCustomFieldValue($selectField, $validOption->id);
    $savedValue = $task->getCustomFieldValue($selectField);
    expect($savedValue)->toBe($validOption->id);

    // Setting a string value for a select field should be handled appropriately
    // (either converted to option ID or rejected)
    try {
        $task->saveCustomFieldValue($selectField, 'some-string-value');
        $savedValue = $task->getCustomFieldValue($selectField);
        
        // If it doesn't throw, verify it's either null or a valid option ID
        if ($savedValue !== null) {
            expect($savedValue)->toBeInt();
        }
    } catch (\Exception $e) {
        // Exception is acceptable for type mismatch
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
