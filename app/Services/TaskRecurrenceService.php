<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Task;
use App\Models\TaskRecurrence;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class TaskRecurrenceService
{
    /**
     * Generate the next occurrence of a recurring task.
     */
    public function generateNextOccurrence(Task $task): ?Task
    {
        $recurrence = $task->recurrence;

        if (! $recurrence instanceof TaskRecurrence || ! $recurrence->is_active) {
            return null;
        }

        $nextDate = $this->calculateNextOccurrenceDate($task, $recurrence);

        if (! $nextDate instanceof \Carbon\Carbon) {
            return null;
        }

        // Clone the task for the next occurrence
        $newTask = $task->replicate(['id', 'created_at', 'updated_at', 'deleted_at']);
        $newTask->start_date = $nextDate;

        // Adjust end date if original task had one
        if ($task->end_date !== null && $task->start_date !== null) {
            $duration = $task->start_date->diffInMinutes($task->end_date);
            $newTask->end_date = $nextDate->copy()->addMinutes($duration);
        }

        $newTask->percent_complete = 0;
        $newTask->save();

        // Copy relationships
        $this->copyTaskRelationships($task, $newTask);

        // Copy custom field values
        $this->copyCustomFieldValues($task, $newTask);

        return $newTask;
    }

    /**
     * Calculate the next occurrence date based on recurrence rules.
     */
    public function calculateNextOccurrenceDate(Task $task, TaskRecurrence $recurrence): ?Carbon
    {
        $baseDate = $task->start_date ?? now();
        $timezone = $recurrence->timezone ?? config('app.timezone');

        $nextDate = \Illuminate\Support\Facades\Date::parse($baseDate, $timezone);

        // Apply frequency and interval
        switch ($recurrence->frequency) {
            case 'daily':
                $nextDate->addDays($recurrence->interval);
                break;

            case 'weekly':
                $nextDate->addWeeks($recurrence->interval);

                // If days_of_week is specified, find the next matching day
                if (! empty($recurrence->days_of_week)) {
                    $nextDate = $this->findNextMatchingWeekday($nextDate, $recurrence->days_of_week);
                }
                break;

            case 'monthly':
                $nextDate->addMonths($recurrence->interval);
                break;

            case 'yearly':
                $nextDate->addYears($recurrence->interval);
                break;

            default:
                return null;
        }

        // Check if we've exceeded the end date
        if ($recurrence->ends_on !== null && $nextDate->isAfter($recurrence->ends_on)) {
            return null;
        }

        // Check if we've exceeded max occurrences
        if ($recurrence->max_occurrences !== null) {
            $occurrenceCount = Task::query()
                ->where('team_id', $task->team_id)
                ->where('title', $task->title)
                ->whereHas('recurrence', function (\Illuminate\Contracts\Database\Query\Builder $query) use ($recurrence): void {
                    $query->where('id', $recurrence->id);
                })
                ->count();

            if ($occurrenceCount >= $recurrence->max_occurrences) {
                return null;
            }
        }

        return $nextDate;
    }

    /**
     * Find the next matching weekday from the given date.
     *
     * @param array<int> $daysOfWeek Array of day numbers (0 = Sunday, 6 = Saturday)
     */
    private function findNextMatchingWeekday(Carbon $date, array $daysOfWeek): Carbon
    {
        $currentDay = $date->dayOfWeek;

        // Sort days to find the next occurrence
        sort($daysOfWeek);

        // Find the next matching day in the current week
        foreach ($daysOfWeek as $day) {
            if ($day > $currentDay) {
                return $date->copy()->next($day);
            }
        }

        // If no matching day in current week, go to first day of next week
        return $date->copy()->addWeek()->next($daysOfWeek[0]);
    }

    /**
     * Copy task relationships to the new occurrence.
     */
    private function copyTaskRelationships(Task $original, Task $new): void
    {
        // Copy assignees
        $new->assignees()->sync($original->assignees->pluck('id'));

        // Copy categories
        $new->categories()->sync($original->categories->pluck('id'));

        // Copy checklist items
        foreach ($original->checklistItems as $item) {
            $new->checklistItems()->create([
                'title' => $item->title,
                'is_completed' => false,
                'position' => $item->position,
            ]);
        }

        // Note: We don't copy dependencies, comments, or time entries
        // as these are specific to each occurrence
    }

    /**
     * Copy custom field values to the new occurrence.
     */
    private function copyCustomFieldValues(Task $original, Task $new): void
    {
        foreach ($original->customFieldValues as $fieldValue) {
            $new->customFieldValues()->create([
                'custom_field_id' => $fieldValue->custom_field_id,
                'string_value' => $fieldValue->string_value,
                'integer_value' => $fieldValue->integer_value,
                'decimal_value' => $fieldValue->decimal_value,
                'boolean_value' => $fieldValue->boolean_value,
                'date_value' => $fieldValue->date_value,
                'datetime_value' => $fieldValue->datetime_value,
                'text_value' => $fieldValue->text_value,
                'json_value' => $fieldValue->json_value,
            ]);
        }
    }

    /**
     * Generate all future occurrences up to a certain date.
     *
     * @return Collection<int, Task>
     */
    public function generateOccurrencesUntil(Task $task, Carbon $until): Collection
    {
        $occurrences = collect();
        $currentTask = $task;

        while (true) {
            $nextTask = $this->generateNextOccurrence($currentTask);

            if (! $nextTask instanceof \App\Models\Task || $nextTask->start_date->isAfter($until)) {
                break;
            }

            $occurrences->push($nextTask);
            $currentTask = $nextTask;
        }

        return $occurrences;
    }

    /**
     * Update all future occurrences when the series is edited.
     */
    public function updateSeriesOccurrences(Task $task, array $updates): int
    {
        if (! $task->recurrence instanceof TaskRecurrence) {
            return 0;
        }

        // Find all future occurrences
        $futureOccurrences = Task::query()
            ->where('team_id', $task->team_id)
            ->where('title', $task->title)
            ->whereHas('recurrence', function (\Illuminate\Contracts\Database\Query\Builder $query) use ($task): void {
                $query->where('id', $task->recurrence->id);
            })
            ->where('start_date', '>', now())
            ->get();

        $count = 0;
        foreach ($futureOccurrences as $occurrence) {
            $occurrence->update($updates);
            $count++;
        }

        return $count;
    }
}
