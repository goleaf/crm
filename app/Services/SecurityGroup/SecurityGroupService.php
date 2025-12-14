<?php

declare(strict_types=1);

namespace App\Services\SecurityGroup;

use App\Models\SecurityGroup;
use App\Models\SecurityGroupRecordAccess;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class SecurityGroupService
{
    /**
     * Create a new security group
     */
    public function createSecurityGroup(array $data): SecurityGroup
    {
        return DB::transaction(function () use ($data): SecurityGroup {
            $group = SecurityGroup::create($data);

            // Log the creation
            $group->logAudit('created', 'group', $group->id, [], $data);

            // Clear relevant caches
            $this->clearGroupCaches($group->team_id);

            return $group;
        });
    }

    /**
     * Update a security group
     */
    public function updateSecurityGroup(SecurityGroup $group, array $data): SecurityGroup
    {
        return DB::transaction(function () use ($group, $data): SecurityGroup {
            $oldValues = $group->toArray();

            $group->update($data);

            // Log the update
            $group->logAudit('updated', 'group', $group->id, $oldValues, $data);

            // Clear relevant caches
            $this->clearGroupCaches($group->team_id);

            return $group->fresh();
        });
    }

    /**
     * Delete a security group
     */
    public function deleteSecurityGroup(SecurityGroup $group): bool
    {
        return DB::transaction(function () use ($group): bool {
            $groupData = $group->toArray();

            // Move children to parent or root level
            $group->children()->update(['parent_id' => $group->parent_id]);

            // Log the deletion
            $group->logAudit('deleted', 'group', $group->id, $groupData, []);

            $teamId = $group->team_id;
            $result = $group->delete();

            // Clear relevant caches
            $this->clearGroupCaches($teamId);

            return $result;
        });
    }

    /**
     * Add a user to a security group
     */
    public function addMemberToGroup(SecurityGroup $group, User $user, array $attributes = []): void
    {
        DB::transaction(function () use ($group, $user, $attributes): void {
            $group->addMember($user, $attributes);

            // Clear user permission cache
            $this->clearUserPermissionCache($user->id);
        });
    }

    /**
     * Remove a user from a security group
     */
    public function removeMemberFromGroup(SecurityGroup $group, User $user): void
    {
        DB::transaction(function () use ($group, $user): void {
            $group->removeMember($user);

            // Clear user permission cache
            $this->clearUserPermissionCache($user->id);
        });
    }

    /**
     * Update member permissions in a security group
     */
    public function updateMemberPermissions(SecurityGroup $group, User $user, array $attributes): void
    {
        DB::transaction(function () use ($group, $user, $attributes): void {
            $oldAttributes = $group->members()->where('user_id', $user->id)->first()?->pivot?->toArray() ?? [];

            $group->members()->updateExistingPivot($user->id, $attributes);

            // Log the update
            $group->logAudit('member_updated', 'membership', $user->id, $oldAttributes, $attributes);

            // Clear user permission cache
            $this->clearUserPermissionCache($user->id);
        });
    }

    /**
     * Grant record access to a security group
     */
    public function grantRecordAccess(SecurityGroup $group, $record, string $accessLevel = 'read', array $fieldPermissions = []): SecurityGroupRecordAccess
    {
        return DB::transaction(function () use ($group, $record, $accessLevel, $fieldPermissions): SecurityGroupRecordAccess {
            $access = $group->grantRecordAccess($record, $accessLevel, $fieldPermissions);

            // Clear record access cache
            $this->clearRecordAccessCache($record);

            return $access;
        });
    }

    /**
     * Revoke record access from a security group
     */
    public function revokeRecordAccess(SecurityGroup $group, $record): void
    {
        DB::transaction(function () use ($group, $record): void {
            $group->revokeRecordAccess($record);

            // Clear record access cache
            $this->clearRecordAccessCache($record);
        });
    }

    /**
     * Check if a user has access to a record through security groups
     */
    public function userHasRecordAccess(User $user, $record, string $action = 'view'): bool
    {
        $cacheKey = "security_group_access:{$user->id}:" . $record::class . ":{$record->getKey()}:{$action}";

        return Cache::remember($cacheKey, 300, function () use ($user, $record, $action): bool {
            // Get all security groups the user is a member of
            $userGroups = $this->getUserSecurityGroups($user);

            foreach ($userGroups as $group) {
                // Check direct record access
                $access = SecurityGroupRecordAccess::where([
                    'group_id' => $group->id,
                    'record_type' => $record::class,
                    'record_id' => $record->getKey(),
                ])->first();

                if ($access && $access->allowsAction($action)) {
                    return true;
                }

                // Check inherited access from parent groups
                if ($group->inherit_permissions) {
                    foreach ($group->ancestors() as $ancestor) {
                        $ancestorAccess = SecurityGroupRecordAccess::where([
                            'group_id' => $ancestor->id,
                            'record_type' => $record::class,
                            'record_id' => $record->getKey(),
                        ])->first();

                        if ($ancestorAccess && $ancestorAccess->allowsAction($action)) {
                            return true;
                        }
                    }
                }
            }

            return false;
        });
    }

    /**
     * Get all security groups a user is a member of (including inherited)
     */
    public function getUserSecurityGroups(User $user): Collection
    {
        $cacheKey = "user_security_groups:{$user->id}";

        return Cache::remember($cacheKey, 600, function () use ($user): Collection {
            $directGroups = SecurityGroup::whereHas('members', function (\Illuminate\Contracts\Database\Query\Builder $query) use ($user): void {
                $query->where('user_id', $user->id);
            })->get();

            $allGroups = collect($directGroups);

            // Add inherited groups from parents
            foreach ($directGroups as $group) {
                if ($group->inherit_permissions) {
                    $allGroups = $allGroups->merge($group->ancestors());
                }
            }

            return $allGroups->unique('id');
        });
    }

    /**
     * Get effective permissions for a user across all their security groups
     */
    public function getUserEffectivePermissions(User $user): array
    {
        $cacheKey = "user_effective_permissions:{$user->id}";

        return Cache::remember($cacheKey, 600, function () use ($user): array {
            $permissions = [];
            $userGroups = $this->getUserSecurityGroups($user);

            foreach ($userGroups as $group) {
                $groupPermissions = $group->getEffectivePermissions($user);
                $permissions = array_merge_recursive($permissions, $groupPermissions);
            }

            return $permissions;
        });
    }

    /**
     * Apply mass assignment rules for a security group
     */
    public function applyMassAssignmentRules(SecurityGroup $group, Collection $records): void
    {
        if (! $group->mass_assignment_settings) {
            return;
        }

        DB::transaction(function () use ($group, $records): void {
            $settings = $group->mass_assignment_settings;

            foreach ($records as $record) {
                // Apply auto-assignment rules
                if ($settings['auto_assign'] ?? false) {
                    $accessLevel = $settings['default_access_level'] ?? 'read';
                    $fieldPermissions = $settings['field_permissions'] ?? [];

                    $group->grantRecordAccess($record, $accessLevel, $fieldPermissions);
                }
            }

            // Log mass assignment
            $group->logAudit('mass_assignment_applied', 'mass_assignment', null, [], [
                'records_count' => $records->count(),
                'settings' => $settings,
            ]);
        });
    }

    /**
     * Get security group hierarchy for a team
     */
    public function getGroupHierarchy(int $teamId): Collection
    {
        $cacheKey = "security_group_hierarchy:{$teamId}";

        return Cache::remember($cacheKey, 600, fn (): Collection => SecurityGroup::where('team_id', $teamId)
            ->securityGroups()
            ->active()
            ->with(['parent', 'children'])
            ->orderBy('level')
            ->orderBy('name')
            ->get());
    }

    /**
     * Validate group hierarchy to prevent circular references
     */
    public function validateHierarchy(SecurityGroup $group, ?int $newParentId): bool
    {
        if (! $newParentId) {
            return true; // Root level is always valid
        }

        if ($newParentId === $group->id) {
            return false; // Cannot be parent of itself
        }

        // Check if new parent is a descendant of this group
        $descendants = $this->getDescendantIds($group);

        return ! in_array($newParentId, $descendants);
    }

    /**
     * Get all descendant IDs for a group
     */
    private function getDescendantIds(SecurityGroup $group): array
    {
        $descendants = [];

        foreach ($group->children as $child) {
            $descendants[] = $child->id;
            $descendants = array_merge($descendants, $this->getDescendantIds($child));
        }

        return $descendants;
    }

    /**
     * Clear group-related caches
     */
    private function clearGroupCaches(int $teamId): void
    {
        Cache::forget("security_group_hierarchy:{$teamId}");

        // Clear user caches for all team members
        $teamUsers = User::whereHas('teams', function (\Illuminate\Contracts\Database\Query\Builder $query) use ($teamId): void {
            $query->where('teams.id', $teamId);
        })->pluck('id');

        foreach ($teamUsers as $userId) {
            $this->clearUserPermissionCache($userId);
        }
    }

    /**
     * Clear user permission caches
     */
    private function clearUserPermissionCache(int $userId): void
    {
        Cache::forget("user_security_groups:{$userId}");
        Cache::forget("user_effective_permissions:{$userId}");

        // Clear specific access caches (pattern-based clearing would be better in production)
        $keys = Cache::getRedis()->keys("security_group_access:{$userId}:*");
        if ($keys) {
            Cache::getRedis()->del($keys);
        }
    }

    /**
     * Clear record access caches
     */
    private function clearRecordAccessCache($record): void
    {
        $recordKey = $record::class . ":{$record->getKey()}";

        // Clear access caches for all users (pattern-based clearing would be better in production)
        $keys = Cache::getRedis()->keys("security_group_access:*:{$recordKey}:*");
        if ($keys) {
            Cache::getRedis()->del($keys);
        }
    }
}
