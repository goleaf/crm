<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Opportunity;
use App\Models\Team;
use App\Models\User;

final readonly class OpportunityObserver
{
    public function creating(Opportunity $opportunity): void
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
            $opportunity->creator_id = $creatorId;
            $opportunity->team_id = $teamId;
            $opportunity->owner_id ??= $creatorId;
        }
    }

    /**
     * Handle the Opportunity "saved" event.
     */
    public function saved(Opportunity $opportunity): void
    {
        //
    }
}
