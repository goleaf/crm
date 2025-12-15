<?php

declare(strict_types=1);

use App\Models\TaskReminder;
use App\Models\Team;
use App\Models\User;
use App\Services\Task\TaskReminderService;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();
    $this->user->teams()->attach($this->team);
    actingAs($this->user);

    $this->reminderService = resolve(TaskReminderService::class);
});

/**
 * **Feature: tasks-activities-enhancement, Property 4: Task reminder management**
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.5**
 *
 * Property: Scheduling a reminder creates a pending reminder, pending reminders are discoverable,
 * and cancellations clear pending reminders.
 */
test('property: task reminders schedule and cancel correctly', function (): void {
    runPropertyTest(function (): void {
        $task = generateTask($this->team, $this->user);
        $remindAt = now()->addMinutes(fake()->numberBetween(5, 180));

        $reminder = $this->reminderService->scheduleReminder($task, $remindAt, $this->user, 'database');

        expect($reminder->status)->toBe('pending');
        expect($task->hasPendingReminders())->toBeTrue();

        $pending = $task->getPendingReminders();
        expect($pending->count())->toBeGreaterThanOrEqual(1);
        expect($pending->contains(fn (TaskReminder $r): bool => $r->id === $reminder->id))->toBeTrue();

        $canceledCount = $this->reminderService->cancelTaskReminders($task);
        expect($canceledCount)->toBeGreaterThanOrEqual(1);

        $task->refresh();
        expect($task->hasPendingReminders())->toBeFalse();
    }, 25);
})->group('property');

/**
 * **Feature: tasks-activities-enhancement, Property 4: Task reminder management**
 * **Validates: Requirements 3.2, 3.3**
 *
 * Property: Only due reminders are sent; future reminders remain pending.
 */
test('property: due reminders are sent while future reminders stay pending', function (): void {
    runPropertyTest(function (): void {
        $task = generateTask($this->team, $this->user);

        $dueReminder = generateTaskReminder($task, $this->user, [
            'remind_at' => now()->subMinutes(fake()->numberBetween(1, 30)),
            'status' => 'pending',
            'sent_at' => null,
            'canceled_at' => null,
        ]);

        $futureReminder = generateTaskReminder($task, $this->user, [
            'remind_at' => now()->addMinutes(fake()->numberBetween(30, 120)),
            'status' => 'pending',
            'sent_at' => null,
            'canceled_at' => null,
        ]);

        $processed = $this->reminderService->sendDueReminders();

        expect($processed->contains(fn (TaskReminder $r): bool => $r->id === $dueReminder->id))->toBeTrue();
        expect($dueReminder->fresh()->status)->toBe('sent');
        expect($futureReminder->fresh()->status)->toBe('pending');
    }, 25);
})->group('property');
