<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\TaskRecurrenceService;
use Illuminate\Console\Command;

final class GenerateRecurringTasksCommand extends Command
{
    protected $signature = 'tasks:generate-recurring {--days=30 : Number of days ahead to generate}';

    protected $description = 'Generate recurring task occurrences';

    public function __construct(
        private readonly TaskRecurrenceService $recurrenceService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $until = now()->addDays($days);

        $this->info("Generating recurring tasks until {$until->toDateString()}");

        $recurringTasks = Task::query()
            ->whereHas('recurrence', function (\Illuminate\Contracts\Database\Query\Builder $query): void {
                $query->where('is_active', true);
            })
            ->get();

        $this->info("Found {$recurringTasks->count()} recurring tasks");

        $totalGenerated = 0;

        foreach ($recurringTasks as $task) {
            $occurrences = $this->recurrenceService->generateOccurrencesUntil($task, $until);
            $totalGenerated += $occurrences->count();

            if ($occurrences->isNotEmpty()) {
                $this->line("Generated {$occurrences->count()} occurrences for task: {$task->title}");
            }
        }

        $this->info("Total occurrences generated: {$totalGenerated}");

        return self::SUCCESS;
    }
}
