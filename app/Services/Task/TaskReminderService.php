<?php

declare(strict_types=1);

namespace App\Services\Task;

use App\Models\Task;
use App\Models\TaskReminder;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class TaskReminderService
{
    /**
     * Schedule a reminder for a task.
     */
    public function scheduleReminder(
        Task $task,
        Carbon $remindAt,
        User $user,
        string $channel = 'database'
    ): TaskReminder {
        return TaskReminder::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'remind_at' => $remindAt,
            'channel' => $channel,
            'status' => 'pending',
        ]);
    }

    /**
     * Send due reminders that are ready to be sent.
     *
     * @return Collection<int, TaskReminder>
     */
    public function sendDueReminders(): Collection
    {
        $reminders = TaskReminder::query()
            ->where('status', 'pending')
            ->whereNull('sent_at')
            ->whereNull('canceled_at')
            ->where('remind_at', '<=', now())
            ->with(['task', 'user'])
            ->get();

        foreach ($reminders as $reminder) {
            $this->sendReminderNotification($reminder);
        }

        return $reminders;
    }

    /**
     * Cancel all reminders for a task.
     */
    public function cancelTaskReminders(Task $task): int
    {
        return TaskReminder::query()
            ->where('task_id', $task->id)
            ->whereNull('sent_at')
            ->whereNull('canceled_at')
            ->update([
                'canceled_at' => now(),
                'status' => 'canceled',
            ]);
    }

    /**
     * Send a reminder notification via the specified channel.
     */
    public function sendReminderNotification(TaskReminder $reminder): void
    {
        // TODO: Implement notification sending logic
        // This will be implemented when notification classes are created

        $reminder->update([
            'sent_at' => now(),
            'status' => 'sent',
        ]);
    }

    /**
     * Get pending reminders for a task.
     *
     * @return Collection<int, TaskReminder>
     */
    public function getPendingReminders(Task $task): Collection
    {
        return TaskReminder::query()
            ->where('task_id', $task->id)
            ->where('status', 'pending')
            ->whereNull('sent_at')
            ->whereNull('canceled_at')
            ->orderBy('remind_at')
            ->get();
    }

    /**
     * Get all reminders for a task.
     *
     * @return Collection<int, TaskReminder>
     */
    public function getTaskReminders(Task $task): Collection
    {
        return TaskReminder::query()
            ->where('task_id', $task->id)
            ->orderBy('remind_at', 'desc')
            ->get();
    }

    /**
     * Cancel a specific reminder.
     */
    public function cancelReminder(TaskReminder $reminder): bool
    {
        if ($reminder->sent_at !== null || $reminder->canceled_at !== null) {
            return false;
        }

        return $reminder->update([
            'canceled_at' => now(),
            'status' => 'canceled',
        ]);
    }

    /**
     * Reschedule a reminder to a new time.
     */
    public function rescheduleReminder(TaskReminder $reminder, Carbon $newRemindAt): bool
    {
        if ($reminder->sent_at !== null || $reminder->canceled_at !== null) {
            return false;
        }

        return $reminder->update([
            'remind_at' => $newRemindAt,
            'status' => 'pending',
        ]);
    }
}

