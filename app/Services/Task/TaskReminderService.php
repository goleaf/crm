<?php

declare(strict_types=1);

namespace App\Services\Task;

use App\Models\Task;
use App\Models\TaskReminder;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class TaskReminderService
{
    /**
     * Valid notification channels for reminders.
     */
    private const VALID_CHANNELS = ['database', 'email', 'sms', 'slack'];

    /**
     * Schedule a reminder for a task.
     *
     * @param Task $task The task to set a reminder for
     * @param Carbon $remindAt When to send the reminder
     * @param User $user The user to remind
     * @param string $channel The notification channel (database, email, sms, slack)
     *
     * @return TaskReminder The created reminder
     *
     * @throws InvalidArgumentException If the channel is invalid
     */
    public function scheduleReminder(
        Task $task,
        Carbon $remindAt,
        User $user,
        string $channel = 'database'
    ): TaskReminder {
        if (! in_array($channel, self::VALID_CHANNELS, true)) {
            throw new InvalidArgumentException(
                "Invalid channel '{$channel}'. Must be one of: " . implode(', ', self::VALID_CHANNELS)
            );
        }

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
     * Cancel all pending reminders for a task.
     *
     * Uses a transaction to ensure all reminders are canceled atomically.
     *
     * @param Task $task The task whose reminders should be canceled
     *
     * @return int The number of reminders canceled
     */
    public function cancelTaskReminders(Task $task): int
    {
        return DB::transaction(function () use ($task) {
            return TaskReminder::query()
                ->where('task_id', $task->id)
                ->whereNull('sent_at')
                ->whereNull('canceled_at')
                ->update([
                    'canceled_at' => now(),
                    'status' => 'canceled',
                ]);
        });
    }

    /**
     * Send a reminder notification via the specified channel.
     *
     * Marks the reminder as sent. Actual notification sending will be
     * implemented when notification classes are created.
     *
     * @param TaskReminder $reminder The reminder to send
     */
    public function sendReminderNotification(TaskReminder $reminder): void
    {
        // TODO: Implement notification sending logic
        // This will be implemented when notification classes are created
        // Example: Notification::send($reminder->user, new TaskReminderNotification($reminder));

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
     *
     * @param TaskReminder $reminder The reminder to cancel
     *
     * @return bool True if canceled successfully, false if already sent/canceled
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
     *
     * @param TaskReminder $reminder The reminder to reschedule
     * @param Carbon $newRemindAt The new reminder time
     *
     * @return bool True if rescheduled successfully, false if already sent/canceled
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

    /**
     * Get valid notification channels.
     *
     * @return array<string> List of valid channels
     */
    public function getValidChannels(): array
    {
        return self::VALID_CHANNELS;
    }

    /**
     * Check if a channel is valid.
     *
     * @param string $channel The channel to validate
     *
     * @return bool True if valid, false otherwise
     */
    public function isValidChannel(string $channel): bool
    {
        return in_array($channel, self::VALID_CHANNELS, true);
    }
}

