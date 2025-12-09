<?php

declare(strict_types=1);

namespace App\Services\Task;

use App\Models\Task;
use App\Models\TaskRecurrence;
use Illuminate\Support\Carbon;

final class TaskRecurrenceService
{
    /**
     * Create a recurrence pattern for a task.
     *
     * @param array{frequency: string, interval: int, days_of_week?: array<int>, starts_on?: Carbon, ends_on?: Carbon, max_occurrences?: int, timezone?: string} $pattern
     */
    public function createRecurrence(Task $task, array $pattern): TaskRecurrence
    {
        return TaskRecurrence::create([
            'task_id' => $task->id,
            'frequency' => $pattern['frequency'],
            'interval' => $pattern['interval'],
            'days_of_week' => $pattern['days_of_week'] ?? null,
            'starts_on' => $pattern['starts_on'] ?? now(),
            'ends_on' => $pattern['ends_on'] ?? null,
            'max_occurrences' => $pattern['max_occurrences'] ?? null,
            'timezone' => $pattern['timezone'] ?? config('app.timezone'),
            'is_active' => true,
        ]);
    }

    /**
     * Generate the next instance of a recurring task.
     */
    public function generateNextInstance(Task $task): ?Task
    {
        $recurrence = $task->recurrence;

        if ($recurrence === null || ! $recurrence->is_active) {
            return null;
        }

        if (! $this->shouldGenerateNext($recurrence)) {
            return null;
        }

        $nextDate = $this->calculateNextDate($recurrence);

        if ($nextDate === null) {
            return null;
        }

        // Create new task instance
        $newTask = $task->replicate(['id', 'created_at', 'updated_at']);
        $newTask->parent_id = $task->id;
        $newTask->start_date = $nextDate;

        if ($task->end_date !== null && $task->start_date !== null) {
            $duration = $task->start_date->diffInDays($task->end_date);
            $newTask->end_date = $nextDate->copy()->addDays($duration);
        }

        $newTask->save();

        // Copy assignees
        $newTask->assignees()->sync($task->assignees->pluck('id'));

        // Copy categories
        $newTask->categories()->sync($task->categories->pluck('id'));

        return $newTask;
    }

    /**
     * Check if the next instance should be generated.
     */
    public function shouldGenerateNext(TaskRecurrence $recurrence): bool
    {
        // Check if recurrence has ended
        if ($recurrence->ends_on !== null && now()->greaterThan($recurrence->ends_on)) {
            return false;
        }

        // Check if max occurrences reached
        if ($recurrence->max_occurrences !== null) {
            $occurrenceCount = Task::query()
                ->where('parent_id', $recurrence->task_id)
                ->count();

            if ($occurrenceCount >= $recurrence->max_occurrences) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate the next occurrence date based on the recurrence pattern.
     */
    public function calculateNextDate(TaskRecurrence $recurrence): ?Carbon
    {
        $task = $recurrence->task;
        $lastDate = $task->start_date ?? now();

        // Get the most recent subtask (instance) if any
        $lastInstance = Task::query()
            ->where('parent_id', $task->id)
            ->orderBy('start_date', 'desc')
            ->first();

        if ($lastInstance !== null && $lastInstance->start_date !== null) {
            $lastDate = $lastInstance->start_date;
        }

        $nextDate = match ($recurrence->frequency) {
            'daily' => $lastDate->copy()->addDays($recurrence->interval),
            'weekly' => $this->calculateNextWeeklyDate($lastDate, $recurrence),
            'monthly' => $lastDate->copy()->addMonths($recurrence->interval),
            'yearly' => $lastDate->copy()->addYears($recurrence->interval),
            default => null,
        };

        // Check if next date is within bounds
        if ($nextDate !== null && $recurrence->ends_on !== null && $nextDate->greaterThan($recurrence->ends_on)) {
            return null;
        }

        return $nextDate;
    }

    /**
     * Calculate next weekly date considering days of week.
     */
    private function calculateNextWeeklyDate(Carbon $lastDate, TaskRecurrence $recurrence): Carbon
    {
        $nextDate = $lastDate->copy()->addWeeks($recurrence->interval);

        // If specific days of week are set, find the next matching day
        if (! empty($recurrence->days_of_week)) {
            $currentDayOfWeek = $nextDate->dayOfWeek;

            // Find the next day in the days_of_week array
            $daysOfWeek = $recurrence->days_of_week;
            sort($daysOfWeek);

            foreach ($daysOfWeek as $day) {
                if ($day >= $currentDayOfWeek) {
                    $nextDate->setDayOfWeek($day);

                    return $nextDate;
                }
            }

            // If no day found in current week, move to next week and use first day
            $nextDate->addWeek()->setDayOfWeek($daysOfWeek[0]);
        }

        return $nextDate;
    }

    /**
     * Update a recurrence pattern.
     *
     * @param array<string, mixed> $pattern
     */
    public function updateRecurrence(TaskRecurrence $recurrence, array $pattern): bool
    {
        return $recurrence->update($pattern);
    }

    /**
     * Deactivate a recurrence pattern.
     */
    public function deactivateRecurrence(TaskRecurrence $recurrence): bool
    {
        return $recurrence->update(['is_active' => false]);
    }

    /**
     * Activate a recurrence pattern.
     */
    public function activateRecurrence(TaskRecurrence $recurrence): bool
    {
        return $recurrence->update(['is_active' => true]);
    }
}

