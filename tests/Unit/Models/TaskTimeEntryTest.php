<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\TaskTimeEntry;
use App\Models\Team;
use App\Models\User;

test('time entry can be created with valid data', function () {
    $team = Team::factory()->create();
    $task = Task::factory()->create(['team_id' => $team->id]);
    $user = User::factory()->create();

    $entry = TaskTimeEntry::factory()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'started_at' => now(),
        'ended_at' => now()->addHours(2),
        'duration_minutes' => 120,
        'is_billable' => true,
        'billing_rate' => 100.00,
    ]);

    expect($entry->task_id)->toBe($task->id)
        ->and($entry->user_id)->toBe($user->id)
        ->and($entry->duration_minutes)->toBe(120)
        ->and($entry->is_billable)->toBeTrue()
        ->and((float) $entry->billing_rate)->toBe(100.00);
});

test('time entry prevents overlapping entries for same user', function () {
    $team = Team::factory()->create();
    $task = Task::factory()->create(['team_id' => $team->id]);
    $user = User::factory()->create();

    // Create first entry from 9am to 11am
    TaskTimeEntry::factory()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'started_at' => now()->setTime(9, 0),
        'ended_at' => now()->setTime(11, 0),
        'duration_minutes' => 120,
    ]);

    // Try to create overlapping entry from 10am to 12pm
    expect(fn () => TaskTimeEntry::factory()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'started_at' => now()->setTime(10, 0),
        'ended_at' => now()->setTime(12, 0),
        'duration_minutes' => 120,
    ]))->toThrow(\DomainException::class, 'Time entry overlaps with an existing entry for this user.');
});

test('time entry prevents duplicate entries', function () {
    $team = Team::factory()->create();
    $task = Task::factory()->create(['team_id' => $team->id]);
    $user = User::factory()->create();

    $startedAt = now()->setTime(9, 0);

    // Create first entry
    TaskTimeEntry::factory()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'started_at' => $startedAt,
        'duration_minutes' => 120,
    ]);

    // Try to create exact duplicate
    expect(fn () => TaskTimeEntry::factory()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'started_at' => $startedAt,
        'duration_minutes' => 120,
    ]))->toThrow(\DomainException::class, 'This time entry already exists.');
});

test('time entry allows non-overlapping entries for same user', function () {
    $team = Team::factory()->create();
    $task = Task::factory()->create(['team_id' => $team->id]);
    $user = User::factory()->create();

    // Create first entry from 9am to 11am
    TaskTimeEntry::factory()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'started_at' => now()->setTime(9, 0),
        'ended_at' => now()->setTime(11, 0),
        'duration_minutes' => 120,
    ]);

    // Create non-overlapping entry from 11am to 1pm
    $entry = TaskTimeEntry::factory()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'started_at' => now()->setTime(11, 0),
        'ended_at' => now()->setTime(13, 0),
        'duration_minutes' => 120,
    ]);

    expect($entry)->not->toBeNull()
        ->and($entry->user_id)->toBe($user->id);
});

test('time entry allows overlapping entries for different users', function () {
    $team = Team::factory()->create();
    $task = Task::factory()->create(['team_id' => $team->id]);
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Create entry for user 1 from 9am to 11am
    TaskTimeEntry::factory()->create([
        'task_id' => $task->id,
        'user_id' => $user1->id,
        'started_at' => now()->setTime(9, 0),
        'ended_at' => now()->setTime(11, 0),
        'duration_minutes' => 120,
    ]);

    // Create overlapping entry for user 2 from 10am to 12pm (should be allowed)
    $entry = TaskTimeEntry::factory()->create([
        'task_id' => $task->id,
        'user_id' => $user2->id,
        'started_at' => now()->setTime(10, 0),
        'ended_at' => now()->setTime(12, 0),
        'duration_minutes' => 120,
    ]);

    expect($entry)->not->toBeNull()
        ->and($entry->user_id)->toBe($user2->id);
});

test('time entry can be updated without triggering overlap validation for itself', function () {
    $team = Team::factory()->create();
    $task = Task::factory()->create(['team_id' => $team->id]);
    $user = User::factory()->create();

    $entry = TaskTimeEntry::factory()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'started_at' => now()->setTime(9, 0),
        'ended_at' => now()->setTime(11, 0),
        'duration_minutes' => 120,
    ]);

    // Update the entry (should not throw overlap exception for itself)
    $entry->update([
        'duration_minutes' => 150,
        'ended_at' => now()->setTime(11, 30),
    ]);

    expect($entry->duration_minutes)->toBe(150);
});

test('time entry skips overlap validation when started_at or ended_at is null', function () {
    $team = Team::factory()->create();
    $task = Task::factory()->create(['team_id' => $team->id]);
    $user = User::factory()->create();

    // Create entry with only started_at (no ended_at)
    $entry = TaskTimeEntry::factory()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'started_at' => now(),
        'ended_at' => null,
        'duration_minutes' => 0,
    ]);

    expect($entry)->not->toBeNull();
});

test('time entry detects overlap when new entry starts during existing entry', function () {
    $team = Team::factory()->create();
    $task = Task::factory()->create(['team_id' => $team->id]);
    $user = User::factory()->create();

    // Existing entry: 9am to 12pm
    TaskTimeEntry::factory()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'started_at' => now()->setTime(9, 0),
        'ended_at' => now()->setTime(12, 0),
        'duration_minutes' => 180,
    ]);

    // New entry starts at 10am (during existing entry)
    expect(fn () => TaskTimeEntry::factory()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'started_at' => now()->setTime(10, 0),
        'ended_at' => now()->setTime(13, 0),
        'duration_minutes' => 180,
    ]))->toThrow(\DomainException::class);
});

test('time entry detects overlap when new entry ends during existing entry', function () {
    $team = Team::factory()->create();
    $task = Task::factory()->create(['team_id' => $team->id]);
    $user = User::factory()->create();

    // Existing entry: 10am to 1pm
    TaskTimeEntry::factory()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'started_at' => now()->setTime(10, 0),
        'ended_at' => now()->setTime(13, 0),
        'duration_minutes' => 180,
    ]);

    // New entry ends at 11am (during existing entry)
    expect(fn () => TaskTimeEntry::factory()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'started_at' => now()->setTime(8, 0),
        'ended_at' => now()->setTime(11, 0),
        'duration_minutes' => 180,
    ]))->toThrow(\DomainException::class);
});

test('time entry detects overlap when new entry completely contains existing entry', function () {
    $team = Team::factory()->create();
    $task = Task::factory()->create(['team_id' => $team->id]);
    $user = User::factory()->create();

    // Existing entry: 10am to 11am
    TaskTimeEntry::factory()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'started_at' => now()->setTime(10, 0),
        'ended_at' => now()->setTime(11, 0),
        'duration_minutes' => 60,
    ]);

    // New entry completely contains existing: 9am to 12pm
    expect(fn () => TaskTimeEntry::factory()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'started_at' => now()->setTime(9, 0),
        'ended_at' => now()->setTime(12, 0),
        'duration_minutes' => 180,
    ]))->toThrow(\DomainException::class);
});
