<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Team;
use App\Models\User;
use Filament\Events\TenantSet;
use Laravel\Jetstream\Features;

final readonly class SwitchTeam
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TenantSet $event): void
    {
        if (Features::hasTeamFeatures()) {
            $user = $event->getUser();

            $team = $event->getTenant();

            if (! $user instanceof User || ! $team instanceof Team) {
                return;
            }

            $user->switchTeam($team);
            setPermissionsTeamId($team->getKey());
        }
    }
}
