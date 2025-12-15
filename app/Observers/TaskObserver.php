<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Milestone;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\Milestones\ProgressTrackingService;
use Illuminate\Support\Facades\DB;

final class TaskObserver
{
    public function creating(Task $task): void
    {
        $webGuard = auth('web');

        if (! $webGuard->check()) {
            return;
        }

        $user = $webGuard->user();

        if (! $user instanceof User) {
            return;
        }

        $team = $user->currentTeam;

        if (! $team instanceof Team) {
            return;
        }

        $creatorId = (int) $webGuard->id();
        $teamId = (int) $team->getKey();

        if ($creatorId > 0 && $teamId > 0) {
            $task->creator_id = $creatorId;
            $task->team_id = $teamId;
        }
    }

    public function created(Task $task): void
    {
        resolve(ActivityService::class)->log(
            $task,
            'created',
            ['title' => $task->title],
        );
    }

    public function updated(Task $task): void
    {
        $changes = $task->getChanges();

        if (! empty($changes)) {
            resolve(ActivityService::class)->log(
                $task,
                'updated',
                $changes,
            );
        }

        if (! array_key_exists('percent_complete', $changes)) {
            return;
        }

        $milestoneIds = DB::table('milestone_task')
            ->where('task_id', $task->getKey())
            ->pluck('milestone_id')
            ->all();

        if ($milestoneIds === []) {
            return;
        }

        $progress = resolve(ProgressTrackingService::class);

        Milestone::query()
            ->whereIn('id', $milestoneIds)
            ->get()
            ->each(function (Milestone $milestone) use ($progress): void {
                $progress->updateFromTasks($milestone);
            });
    }

    public function deleted(Task $task): void
    {
        resolve(ActivityService::class)->log(
            $task,
            'deleted',
            ['title' => $task->title],
        );
    }
}
