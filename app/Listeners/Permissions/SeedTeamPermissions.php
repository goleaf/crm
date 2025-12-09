<?php

declare(strict_types=1);

namespace App\Listeners\Permissions;

use App\Models\Team;
use App\Models\User;
use App\Permissions\PermissionService;
use Laravel\Jetstream\Events\TeamCreated;

final readonly class SeedTeamPermissions
{
    public function __construct(
        private PermissionService $permissions,
    ) {}

    public function handle(TeamCreated $event): void
    {
        $team = $event->team;

        if (! $team instanceof Team) {
            return;
        }

        $this->permissions->syncTeamDefinitions($team);

        $owner = $team->owner;

        if ($owner instanceof User) {
            $this->permissions->syncMembership($owner, $team, 'owner');
        }
    }
}
