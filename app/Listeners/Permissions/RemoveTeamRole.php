<?php

declare(strict_types=1);

namespace App\Listeners\Permissions;

use App\Models\Team;
use App\Models\User;
use App\Permissions\PermissionService;
use Laravel\Jetstream\Events\TeamMemberRemoved;

final readonly class RemoveTeamRole
{
    public function __construct(
        private PermissionService $permissions,
    ) {}

    public function handle(TeamMemberRemoved $event): void
    {
        $team = $event->team;
        $user = $event->user;

        if (! $team instanceof Team || ! $user instanceof User) {
            return;
        }

        $this->permissions->removeMembership($user, $team);
    }
}
