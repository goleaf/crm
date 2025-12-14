<?php

declare(strict_types=1);

namespace App\Services\Role;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

final readonly class RoleManagementService
{
    public function __construct(
        private int $cacheTtl = 3600,
    ) {}

    /**
     * Create a new role with permissions
     */
    public function createRole(array $data, array $permissions = []): Role
    {
        return DB::transaction(function () use ($data, $permissions) {
            // Validate no circular inheritance
            if (isset($data['parent_role_id']) && $data['parent_role_id']) {
                $this->validateNoCircularInheritance($data['parent_role_id']);
            }

            $role = Role::create($data);

            if ($permissions !== []) {
                $role->syncPermissions($permissions);
            }

            $role->logChange('created', $data);

            $this->clearRoleCache();

            return $role;
        });
    }

    /**
     * Update a role
     */
    public function updateRole(Role $role, array $data, ?array $permissions = null): Role
    {
        return DB::transaction(function () use ($role, $data, $permissions): \App\Models\Role {
            $originalData = $role->toArray();

            // Validate no circular inheritance
            if (isset($data['parent_role_id']) && $data['parent_role_id']) {
                $this->validateNoCircularInheritance($data['parent_role_id'], $role->id);
            }

            $role->update($data);

            if ($permissions !== null) {
                $role->syncPermissions($permissions);
            }

            $changes = array_diff_assoc($data, $originalData);
            if ($changes !== [] || $permissions !== null) {
                $role->logChange('updated', [
                    'changes' => $changes,
                    'permissions_updated' => $permissions !== null,
                ]);
            }

            $this->clearRoleCache();

            return $role;
        });
    }

    /**
     * Delete a role
     */
    public function deleteRole(Role $role): bool
    {
        return DB::transaction(function () use ($role) {
            // Check if role has child roles
            if ($role->childRoles()->exists()) {
                throw new \InvalidArgumentException('Cannot delete role with child roles. Please reassign or delete child roles first.');
            }

            // Check if role has users assigned
            if ($role->users()->exists()) {
                throw new \InvalidArgumentException('Cannot delete role with assigned users. Please reassign users first.');
            }

            $role->logChange('deleted');
            $deleted = $role->delete();

            $this->clearRoleCache();

            return $deleted;
        });
    }

    /**
     * Assign role to user
     */
    public function assignRoleToUser(User $user, Role $role, ?int $teamId = null): void
    {
        DB::transaction(function () use ($user, $role, $teamId): void {
            if ($teamId) {
                $team = \App\Models\Team::find($teamId);
                $user->assignRole($role->name, $team);
            } else {
                $user->assignRole($role->name);
            }

            $role->logChange('user_assigned', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'team_id' => $teamId,
            ]);

            $this->clearUserPermissionCache($user);
        });
    }

    /**
     * Remove role from user
     */
    public function removeRoleFromUser(User $user, Role $role): void
    {
        DB::transaction(function () use ($user, $role): void {
            $user->removeRole($role->name);

            $role->logChange('user_removed', [
                'user_id' => $user->id,
                'user_name' => $user->name,
            ]);

            $this->clearUserPermissionCache($user);
        });
    }

    /**
     * Create role from template
     */
    public function createFromTemplate(Role $template, array $attributes): Role
    {
        return DB::transaction(function () use ($template, $attributes): \App\Models\Role {
            $role = Role::createFromTemplate($template, $attributes);

            $role->logChange('created_from_template', [
                'template_id' => $template->id,
                'template_name' => $template->name,
            ]);

            $this->clearRoleCache();

            return $role;
        });
    }

    /**
     * Get role permissions matrix
     */
    public function getRolePermissionsMatrix(Role $role): array
    {
        return Cache::remember(
            "role_permissions_matrix_{$role->id}",
            $this->cacheTtl,
            function () use ($role): array {
                $permissions = $role->getAllPermissions();
                $matrix = [];

                foreach ($permissions as $permission) {
                    [$action, $resource] = explode(':', (string) $permission->name, 2);
                    $matrix[$resource][$action] = true;
                }

                return $matrix;
            },
        );
    }

    /**
     * Get available permissions grouped by resource
     */
    public function getAvailablePermissions(): Collection
    {
        return Cache::remember(
            'available_permissions_grouped',
            $this->cacheTtl,
            fn () => Permission::all()->groupBy(function ($permission): string {
                [$action, $resource] = explode(':', (string) $permission->name, 2);

                return $resource;
            }),
        );
    }

    /**
     * Get role templates
     */
    public function getRoleTemplates(?int $teamId = null): Collection
    {
        $cacheKey = 'role_templates_' . ($teamId ?? 'global');

        return Cache::remember(
            $cacheKey,
            $this->cacheTtl,
            fn () => Role::templates()
                ->forTeam($teamId)
                ->with(['permissions'])
                ->get(),
        );
    }

    /**
     * Validate no circular inheritance
     */
    private function validateNoCircularInheritance(int $parentRoleId, ?int $currentRoleId = null): void
    {
        $visited = [];
        $current = Role::find($parentRoleId);

        while ($current && $current->parent_role_id) {
            if ($current->id === $currentRoleId || in_array($current->id, $visited, true)) {
                throw new \InvalidArgumentException('Circular role inheritance detected.');
            }
            $visited[] = $current->id;
            $current = $current->parentRole;
        }
    }

    /**
     * Clear role-related cache
     */
    private function clearRoleCache(): void
    {
        Cache::forget('available_permissions_grouped');
        Cache::tags(['roles'])->flush();
    }

    /**
     * Clear user permission cache
     */
    private function clearUserPermissionCache(User $user): void
    {
        Cache::forget("spatie.permission.cache.{$user->id}");
    }

    /**
     * Get role hierarchy for a team
     */
    public function getRoleHierarchy(?int $teamId = null): Collection
    {
        $cacheKey = 'role_hierarchy_' . ($teamId ?? 'global');

        return Cache::remember(
            $cacheKey,
            $this->cacheTtl,
            fn () => Role::forTeam($teamId)
                ->with(['parentRole', 'childRoles', 'permissions'])
                ->get()
                ->groupBy('parent_role_id'),
        );
    }

    /**
     * Bulk assign permissions to role
     */
    public function bulkAssignPermissions(Role $role, array $permissionMatrix): void
    {
        DB::transaction(function () use ($role, $permissionMatrix): void {
            $permissions = [];

            foreach ($permissionMatrix as $resource => $actions) {
                foreach ($actions as $action => $granted) {
                    if ($granted) {
                        $permissions[] = "{$action}:{$resource}";
                    }
                }
            }

            $role->syncPermissions($permissions);

            $role->logChange('permissions_bulk_updated', [
                'permissions_count' => count($permissions),
                'matrix' => $permissionMatrix,
            ]);

            $this->clearRoleCache();
        });
    }
}
