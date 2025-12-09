<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\TaskReminder;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendTaskReminderJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public TaskReminder $reminder,
    ) {}

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "task-reminder-{$this->reminder->id}";
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Check if reminder was already sent or canceled
        if ($this->reminder->sent_at !== null || $this->reminder->canceled_at !== null) {
            return;
        }

        // Check if reminder time has passed
        if ($this->reminder->remind_at->isFuture()) {
            return;
        }

        $task = $this->reminder->task;
        $user = $this->reminder->user;

        if (! $user instanceof User || ! $task) {
            $this->reminder->update(['status' => 'failed']);

            return;
        }

        // Send notification based on channel
        match ($this->reminder->channel) {
            'database' => $this->sendDatabaseNotification($user),
            'mail' => $this->sendEmailNotification($user),
            default => $this->sendDatabaseNotification($user),
        };

        // Mark as sent
        $this->reminder->update([
            'sent_at' => now(),
            'status' => 'sent',
        ]);
    }

    /**
     * Send database notification.
     */
    private function sendDatabaseNotification(User $user): void
    {
        $task = $this->reminder->task;

        Notification::make()
            ->title(__('app.notifications.task_reminder'))
            ->body($task->title)
            ->icon('heroicon-o-bell')
            ->iconColor('warning')
            ->sendToDatabase($user);
    }

    /**
     * Send email notification.
     */
    private function sendEmailNotification(User $user): void
    {
        // TODO: Implement email notification
        // For now, fall back to database notification
        $this->sendDatabaseNotification($user);
    }
}
