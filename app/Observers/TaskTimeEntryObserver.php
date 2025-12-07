<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\TaskTimeEntry;

final class TaskTimeEntryObserver
{
    /**
     * Handle the TaskTimeEntry "created" event.
     */
    public function created(TaskTimeEntry $timeEntry): void
    {
        $this->updateProjectCosts($timeEntry);
    }

    /**
     * Handle the TaskTimeEntry "updated" event.
     */
    public function updated(TaskTimeEntry $timeEntry): void
    {
        $this->updateProjectCosts($timeEntry);
    }

    /**
     * Handle the TaskTimeEntry "deleted" event.
     */
    public function deleted(TaskTimeEntry $timeEntry): void
    {
        $this->updateProjectCosts($timeEntry);
    }

    /**
     * Update project actual costs when time entry changes.
     */
    private function updateProjectCosts(TaskTimeEntry $timeEntry): void
    {
        $task = $timeEntry->task;

        if ($task === null) {
            return;
        }

        // Update all projects associated with this task
        foreach ($task->projects as $project) {
            $project->updateActualCost();
        }
    }
}
