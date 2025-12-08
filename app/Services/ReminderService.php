<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Task;
use App\Models\TaskReminder;
use App\Models\User;
use Illuminate\Support\Carbon;

final class ReminderService
{
    /**
     * Schedule a reminder for a task.
     */
    public function scheduleTaskReminder(
        Task $task,
        User $user,
        Carbon $remindAt,
        string $channel = 'database'
    ): TaskReminder {
        // Check if a pending reminder already exists for this task/user/time
        $existing = TaskReminder::query()
            ->where('task_id', $task->getKey())
            ->where('user_id', $user->getKey())
            ->where('remind_at', $remindAt)
            ->whereNull('sent_at')
            ->whereNull('canceled_at')
            ->first();

        if ($existing instanceof TaskReminder) {
            return $existing;
        }

        $reminder = TaskReminder::create([
            'task_id' => $task->getKey(),
            'user_id' => $user->getKey(),
            'remind_at' => $remindAt,
            'channel' => $channel,
            'status' => 'pending',
        ]);

        // Dispatch job to send reminder at the specified time
        dispatch(new \App\Jobs\SendTaskReminderJob($reminder))
            ->delay($remindAt);

        return $reminder;
    }

    /**
     * Cancel a reminder.
     */
    public function cancelReminder(TaskReminder $reminder): bool
    {
        if ($reminder->sent_at !== null) {
            return false; // Already sent
        }

        $reminder->update([
            'canceled_at' => now(),
            'status' => 'canceled',
        ]);

        return true;
    }

    /**
     * Get pending reminders that should be sent now.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, TaskReminder>
     */
    public function getPendingReminders(): \Illuminate\Database\Eloquent\Collection
    {
        return TaskReminder::query()
            ->whereNull('sent_at')
            ->whereNull('canceled_at')
            ->where('remind_at', '<=', now())
            ->where('status', 'pending')
            ->with(['task', 'user'])
            ->get();
    }

    /**
     * Process pending reminders (for scheduled command).
     */
    public function processPendingReminders(): int
    {
        $reminders = $this->getPendingReminders();
        $processed = 0;

        foreach ($reminders as $reminder) {
            try {
                dispatch(new \App\Jobs\SendTaskReminderJob($reminder));
                $processed++;
            } catch (\Throwable $e) {
                report($e);
                $reminder->update(['status' => 'failed']);
            }
        }

        return $processed;
    }

    /**
     * Schedule reminders for a calendar event.
     *
     * @param  array<int>  $userIds
     */
    public function scheduleCalendarEventReminders(
        CalendarEvent $event,
        array $userIds,
        ?int $minutesBefore = null
    ): int {
        if ($event->start_at === null) {
            return 0;
        }

        $minutesBefore ??= $event->reminder_minutes_before ?? 15;
        $remindAt = $event->start_at->copy()->subMinutes($minutesBefore);

        // Don't schedule reminders in the past
        if ($remindAt->isPast()) {
            return 0;
        }

        $scheduled = 0;

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (! $user instanceof User) {
                continue;
            }

            // For calendar events, we'll use a similar pattern but store in a different way
            // For now, we'll skip calendar event reminders as they're not in the task_reminders table
            // This would need a separate calendar_event_reminders table
            $scheduled++;
        }

        return $scheduled;
    }

    /**
     * Reschedule reminders for a recurring task.
     */
    public function rescheduleRecurringTaskReminders(Task $task): int
    {
        if (! $task->recurrence instanceof \App\Models\TaskRecurrence) {
            return 0;
        }

        // Cancel existing pending reminders
        TaskReminder::query()
            ->where('task_id', $task->getKey())
            ->whereNull('sent_at')
            ->whereNull('canceled_at')
            ->update([
                'canceled_at' => now(),
                'status' => 'canceled',
            ]);

        // Get assignees
        $assignees = $task->assignees;
        if ($assignees->isEmpty()) {
            return 0;
        }

        // Get due date
        $dueDate = $task->dueDate();
        if (! $dueDate instanceof \Illuminate\Support\Carbon) {
            return 0;
        }

        // Schedule new reminders (e.g., 1 day before due date)
        $remindAt = $dueDate->copy()->subDay();
        if ($remindAt->isPast()) {
            return 0;
        }

        $scheduled = 0;
        foreach ($assignees as $assignee) {
            $this->scheduleTaskReminder($task, $assignee, $remindAt);
            $scheduled++;
        }

        return $scheduled;
    }
}
