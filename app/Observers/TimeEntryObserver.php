<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\TimeEntry;
use App\Services\ActivityService;

final class TimeEntryObserver
{
    public function created(TimeEntry $entry): void
    {
        resolve(ActivityService::class)->log(
            $entry,
            'created',
            [
                'date' => $entry->date?->toDateString(),
                'duration_minutes' => $entry->duration_minutes,
                'project_id' => $entry->project_id,
                'task_id' => $entry->task_id,
                'is_billable' => $entry->is_billable,
            ],
        );
    }

    public function updated(TimeEntry $entry): void
    {
        $changes = $entry->getChanges();

        if ($changes === []) {
            return;
        }

        resolve(ActivityService::class)->log(
            $entry,
            'updated',
            $changes,
        );
    }

    public function deleted(TimeEntry $entry): void
    {
        resolve(ActivityService::class)->log(
            $entry,
            'deleted',
            [
                'date' => $entry->date?->toDateString(),
                'duration_minutes' => $entry->duration_minutes,
                'project_id' => $entry->project_id,
                'task_id' => $entry->task_id,
                'is_billable' => $entry->is_billable,
            ],
        );
    }
}
