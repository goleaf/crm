<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Command;

final class ProcessPendingRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending reminders that are due';

    /**
     * Execute the console command.
     */
    public function handle(ReminderService $reminderService): int
    {
        $this->info('Processing pending reminders...');

        $processed = $reminderService->processPendingReminders();

        $this->info("Processed {$processed} reminders.");

        return self::SUCCESS;
    }
}
