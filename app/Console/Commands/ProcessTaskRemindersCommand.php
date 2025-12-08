<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TaskReminder;
use Illuminate\Console\Command;

final class ProcessTaskRemindersCommand extends Command
{
    protected $signature = 'tasks:process-reminders';

    protected $description = 'Process pending task reminders';

    public function handle(): int
    {
        $pendingReminders = TaskReminder::query()
            ->where('status', 'pending')
            ->whereNull('sent_at')
            ->whereNull('canceled_at')
            ->where('remind_at', '<=', now())
            ->get();

        $this->info("Found {$pendingReminders->count()} pending reminders");

        foreach ($pendingReminders as $reminder) {
            dispatch(new \App\Jobs\SendTaskReminderJob($reminder));
        }

        $this->info('Dispatched reminder jobs');

        return self::SUCCESS;
    }
}
