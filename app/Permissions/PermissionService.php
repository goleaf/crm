<?php

declare(strict_types=1);

namespace App\Permissions;

use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

final class PermissionService
{
    /**
     * Ensure a team has the configured permissions/roles and assign the mapped role to a member.
     */
    public function syncMembership(User $user, Team $team, string $teamRole): void
    {
        $this->syncTeamDefinitions($team);

        $role = $this->mapTeamRole($teamRole);

        setPermissionsTeamId($team->getKey());

        $user->syncRoles([$role]);
    }

    public function removeMembership(User $user, Team $team): void
    {
        setPermissionsTeamId($team->getKey());

        $user->syncRoles([]);
    }

    /**
     * Seed/refresh roles and permissions for the given team.
     */
    public function syncTeamDefinitions(Team $team): void
    {
        $definitions = Config::get('permission.defaults', []);
        $guard = $definitions['guard'] ?? 'web';

        $permissionNames = $this->buildPermissionNames($definitions);

        setPermissionsTeamId($team->getKey());

        foreach ($permissionNames as $permission) {
            Permission::findOrCreate($permission, $guard);
        }

        foreach ($definitions['roles'] ?? [] as $name => $roleConfig) {
            $role = Role::findOrCreate($name, $guard);

            $resolvedPermissions = $this->resolveRolePermissions(
                $roleConfig,
                $definitions,
                $permissionNames
            );

            $role->syncPermissions($resolvedPermissions);
        }
    }

    /**
     * Convert resource/action definitions into permission names.
     *
     * @return array<int, string>
     */
    private function buildPermissionNames(array $definitions): array
    {
        $resources = $definitions['resources'] ?? [];
        $custom = $definitions['custom_permissions'] ?? [];

        $resourcePermissions = collect($resources)
            ->map(fn (array $actions, string $resource): array => collect($actions)
                ->map(fn (string $action): string => "{$resource}.{$action}")
                ->all())
            ->flatten();

        return $resourcePermissions
            ->merge($custom)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Resolve permissions for a role including inheritance and permission sets.
     *
     * @return array<int, string>
     */
    private function resolveRolePermissions(
        array $roleConfig,
        array $definitions,
        array $availablePermissions
    ): array {
        $resources = $definitions['resources'] ?? [];
        $permissionSets = $definitions['permission_sets'] ?? [];

        $permissions = collect($roleConfig['permissions'] ?? [])
            ->merge($this->expandPermissionSets($roleConfig['permission_sets'] ?? [], $permissionSets))
            ->merge($this->expandInheritedRoles($roleConfig['inherits'] ?? [], $definitions, $availablePermissions))
            ->flatMap(fn (string $permission): array => $this->expandPermissionToken($permission, $resources, $availablePermissions))
            ->unique()
            ->values();

        if ($permissions->contains('*')) {
            return $availablePermissions;
        }

        return $permissions
            ->filter(fn (string $permission): bool => in_array($permission, $availablePermissions, true))
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $permissionSets
     * @return Collection<int, string>
     */
    private function expandPermissionSets(array $permissionSets, array $definitions): Collection
    {
        return collect($permissionSets)
            ->flatMap(fn (string $set): array => Arr::get($definitions, $set, []));
    }

    private function expandInheritedRoles(
        array $roles,
        array $definitions,
        array $availablePermissions
    ): Collection {
        return collect($roles)
            ->flatMap(function (string $role) use ($definitions, $availablePermissions): array {
                $config = Arr::get($definitions, "roles.{$role}", []);

                return $this->resolveRolePermissions($config, $definitions, $availablePermissions);
            });
    }

    /**
     * Expand tokens like resource.* or * into concrete permission names.
     *
     * @param  array<string, array<int, string>>  $resources
     * @param  list<string>  $availablePermissions
     * @return list<string>
     */
    private function expandPermissionToken(
        string $permission,
        array $resources,
        array $availablePermissions
    ): array {
        if ($permission === '*') {
            return ['*'];
        }

        if (str_ends_with($permission, '.*')) {
            $resource = Str::beforeLast($permission, '.*');
            $actions = $resources[$resource] ?? [];

            return collect($actions)
                ->map(fn (string $action): string => "{$resource}.{$action}")
                ->filter(fn (string $name): bool => in_array($name, $availablePermissions, true))
                ->values()
                ->all();
        }

        return [$permission];
    }

    private function mapTeamRole(string $teamRole): string
    {
        $map = Config::get('permission.defaults.team_role_map', []);

        return $map[$teamRole] ?? $teamRole;
    }
}
