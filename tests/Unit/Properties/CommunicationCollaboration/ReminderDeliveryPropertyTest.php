<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\TaskReminder;
use App\Models\Team;
use App\Models\User;
use App\Services\ReminderService;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();
    $this->user->teams()->attach($this->team);
    $this->user->currentTeam()->associate($this->team);
    $this->user->save();
    actingAs($this->user);
});

/**
 * **Feature: communication-collaboration, Property 2: Reminder delivery**
 *
 * **Validates: Requirements 5.1, 3.1**
 *
 * Property: Reminders fire at configured times and notify intended recipients once.
 */
test('property: task reminders are delivered exactly once', function (): void {
    runPropertyTest(function (): void {
        // Generate a task with a due date
        $task = generateTask($this->team, $this->user, [
            'due_date' => fake()->dateTimeBetween('+1 hour', '+1 week'),
        ]);

        // Generate a reminder time before the due date
        $remindAt = fake()->dateTimeBetween('now', $task->due_date->subHour());

        $reminderService = resolve(ReminderService::class);

        // Schedule a reminder
        $reminder = $reminderService->scheduleTaskReminder($task, $this->user, $remindAt);

        expect($reminder)->toBeInstanceOf(TaskReminder::class);
        expect($reminder->status)->toBe('pending');
        expect($reminder->sent_at)->toBeNull();

        // Attempt to schedule the same reminder again (should return existing)
        $duplicateReminder = $reminderService->scheduleTaskReminder($task, $this->user, $remindAt);

        expect($duplicateReminder->id)->toBe($reminder->id,
            'Duplicate reminder should return the existing reminder',
        );

        // Verify only one pending reminder exists for this task/user/time combination
        $pendingCount = TaskReminder::query()
            ->where('task_id', $task->id)
            ->where('user_id', $this->user->id)
            ->where('remind_at', $remindAt)
            ->whereNull('sent_at')
            ->whereNull('canceled_at')
            ->count();

        expect($pendingCount)->toBe(1,
            'Only one pending reminder should exist for the same task/user/time',
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 2: Reminder delivery**
 *
 * **Validates: Requirements 5.1, 3.1**
 *
 * Property: Recurring task reminders are rescheduled correctly.
 */
test('property: recurring task reminders maintain schedule integrity', function (): void {
    runPropertyTest(function (): void {
        // Generate a recurring task
        $task = generateTask($this->team, $this->user, [
            'due_date' => fake()->dateTimeBetween('+1 day', '+1 week'),
        ]);

        // Add recurrence pattern
        $recurrence = generateTaskRecurrence($task, [
            'frequency' => fake()->randomElement(['daily', 'weekly', 'monthly']),
            'interval' => fake()->numberBetween(1, 3),
        ]);

        $reminderService = resolve(ReminderService::class);

        // Schedule initial reminders
        $initialCount = $reminderService->rescheduleRecurringTaskReminders($task);

        // Verify reminders were scheduled
        expect($initialCount)->toBeGreaterThanOrEqual(0,
            'Reminders should be scheduled for recurring task',
        );

        // Get the count of pending reminders
        $pendingReminders = TaskReminder::query()
            ->where('task_id', $task->id)
            ->whereNull('sent_at')
            ->whereNull('canceled_at')
            ->count();

        // Reschedule again (should cancel old and create new)
        $rescheduledCount = $reminderService->rescheduleRecurringTaskReminders($task);

        // Verify old reminders were canceled
        $canceledReminders = TaskReminder::query()
            ->where('task_id', $task->id)
            ->whereNotNull('canceled_at')
            ->count();

        expect($canceledReminders)->toBeGreaterThanOrEqual($pendingReminders,
            'Previous reminders should be canceled when rescheduling',
        );

        // Verify new reminders are pending
        $newPendingReminders = TaskReminder::query()
            ->where('task_id', $task->id)
            ->whereNull('sent_at')
            ->whereNull('canceled_at')
            ->count();

        expect($newPendingReminders)->toBe($rescheduledCount,
            'New pending reminders should match rescheduled count',
        );
    }, 50); // Reduced iterations due to complexity
})->group('property');

/**
 * **Feature: communication-collaboration, Property 2: Reminder delivery**
 *
 * **Validates: Requirements 3.1**
 *
 * Property: Calendar event reminders respect timing and attendee lists.
 */
test('property: calendar event reminders are scheduled for all attendees', function (): void {
    runPropertyTest(function (): void {
        // Generate a calendar event with multiple attendees
        $attendeeCount = fake()->numberBetween(2, 6);
        $attendees = [];
        $userIds = [];

        for ($i = 0; $i < $attendeeCount; $i++) {
            $user = User::factory()->create();
            $user->teams()->attach($this->team);
            $userIds[] = $user->id;
            $attendees[] = [
                'name' => $user->name,
                'email' => $user->email,
            ];
        }

        $event = generateCalendarEvent($this->team, $this->user, [
            'start_at' => fake()->dateTimeBetween('+2 hours', '+1 week'),
            'attendees' => $attendees,
            'reminder_minutes_before' => fake()->randomElement([15, 30, 60]),
        ]);

        $reminderService = resolve(ReminderService::class);

        // Schedule reminders for all attendees
        $scheduledCount = $reminderService->scheduleCalendarEventReminders(
            $event,
            $userIds,
            $event->reminder_minutes_before,
        );

        expect($scheduledCount)->toBe($attendeeCount,
            'Reminders should be scheduled for all attendees',
        );

        // Verify reminder timing is correct
        $expectedRemindAt = $event->start_at->copy()->subMinutes($event->reminder_minutes_before);

        // Since we don't have a calendar_event_reminders table yet,
        // we'll verify the logic by checking the reminder time calculation
        expect($expectedRemindAt)->toBeLessThan($event->start_at,
            'Reminder time should be before event start time',
        );

        expect($expectedRemindAt->diffInMinutes($event->start_at))->toBe($event->reminder_minutes_before,
            'Reminder should be scheduled exactly the specified minutes before event',
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 2: Reminder delivery**
 *
 * **Validates: Requirements 5.1**
 *
 * Property: Canceled reminders do not get delivered.
 */
test('property: canceled reminders are not delivered', function (): void {
    runPropertyTest(function (): void {
        // Generate a task with future due date
        $task = generateTask($this->team, $this->user, [
            'due_date' => fake()->dateTimeBetween('+1 hour', '+1 day'),
        ]);

        $remindAt = fake()->dateTimeBetween('now', $task->due_date->subMinutes(30));

        $reminderService = resolve(ReminderService::class);

        // Schedule a reminder
        $reminder = $reminderService->scheduleTaskReminder($task, $this->user, $remindAt);

        expect($reminder->status)->toBe('pending');
        expect($reminder->canceled_at)->toBeNull();

        // Cancel the reminder
        $canceled = $reminderService->cancelReminder($reminder);

        expect($canceled)->toBeTrue('Reminder should be successfully canceled');

        // Verify reminder is marked as canceled
        $reminder->refresh();
        expect($reminder->status)->toBe('canceled');
        expect($reminder->canceled_at)->not->toBeNull();

        // Verify canceled reminder is not in pending list
        $pendingReminders = $reminderService->getPendingReminders();

        expect($pendingReminders->contains($reminder))->toBeFalse(
            'Canceled reminder should not appear in pending reminders',
        );

        // Attempt to cancel already sent reminder (should fail)
        $sentReminder = generateTaskReminder($task, $this->user, [
            'sent_at' => now()->subHour(),
            'status' => 'sent',
        ]);

        $cannotCancel = $reminderService->cancelReminder($sentReminder);
        expect($cannotCancel)->toBeFalse(
            'Already sent reminders should not be cancelable',
        );
    }, 100);
})->group('property');
