<?php

declare(strict_types=1);

namespace App\Listeners\Permissions;

use App\Models\Team;
use App\Models\User;
use App\Permissions\PermissionService;
use Laravel\Jetstream\Events\TeamMemberAdded;
use Laravel\Jetstream\Events\TeamMemberUpdated;

final readonly class SyncTeamMemberRole
{
    public function __construct(
        private PermissionService $permissions
    ) {}

    public function handle(TeamMemberAdded|TeamMemberUpdated $event): void
    {
        $team = $event->team;
        $user = $event->user;

        if (! $team instanceof Team || ! $user instanceof User) {
            return;
        }

        $role = $this->resolveTeamRole($team, $user);

        $this->permissions->syncMembership($user, $team, $role);
    }

    private function resolveTeamRole(Team $team, User $user): string
    {
        $membership = $team->users()
            ->whereKey($user->getKey())
            ->first()?->membership;

        return $membership?->role ?? 'member';
    }
}
