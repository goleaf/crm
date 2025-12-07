<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\People;
use App\Models\Team;
use App\Models\User;

final readonly class PeopleObserver
{
    public function creating(People $people): void
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
            $people->creator_id = $creatorId;
            $people->team_id = $teamId;
        }
    }

    /**
     * Handle the People "saved" event.
     * Invalidate AI summary when person data changes.
     */
    public function saved(People $people): void
    {
        $people->ensureEmailsFromColumns();
        $people->syncEmailColumns();
        $people->invalidateAiSummary();
    }
}
