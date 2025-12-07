<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Task;
use App\Models\Team;
use App\Models\User;

final readonly class TaskObserver
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

    public function saved(Task $task): void
    {
        //
    }

    public function deleted(Task $task): void
    {
        //
    }
}
