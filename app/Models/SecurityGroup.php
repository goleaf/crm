<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

final class SecurityGroup extends Model
{
    use HasCreator;
    use HasFactory;
    use HasTeam;

    protected $table = 'groups';

    protected $fillable = [
        'name',
        'description',
        'team_id',
        'parent_id',
        'level',
        'path',
        'is_security_group',
        'inherit_permissions',
        'owner_only_access',
        'group_only_access',
        'layout_overrides',
        'custom_layouts',
        'auto_assignment_rules',
        'mass_assignment_settings',
        'enable_broadcast',
        'broadcast_settings',
        'is_primary_group',
        'allow_login_as',
        'login_as_permissions',
        'record_level_permissions',
        'field_level_permissions',
        'active',
        'metadata',
    ];

    protected $casts = [
        'is_security_group' => 'boolean',
        'inherit_permissions' => 'boolean',
        'owner_only_access' => 'boolean',
        'group_only_access' => 'boolean',
        'layout_overrides' => 'array',
        'custom_layouts' => 'array',
        'auto_assignment_rules' => 'array',
        'mass_assignment_settings' => 'array',
        'enable_broadcast' => 'boolean',
        'broadcast_settings' => 'array',
        'is_primary_group' => 'boolean',
        'allow_login_as' => 'boolean',
        'login_as_permissions' => 'array',
        'record_level_permissions' => 'array',
        'field_level_permissions' => 'array',
        'active' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        self::creating(function (SecurityGroup $group): void {
            if ($group->parent_id) {
                $parent = static::find($group->parent_id);
                if ($parent) {
                    $group->level = $parent->level + 1;
                    $group->path = $parent->path ? $parent->path . '/' . $parent->id : (string) $parent->id;
                }
            } else {
                $group->level = 0;
                $group->path = null;
            }
        });

        self::updated(function (SecurityGroup $group): void {
            if ($group->wasChanged('parent_id')) {
                $group->updateHierarchy();
            }
        });
    }

    /**
     * Parent security group relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\SecurityGroup, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(SecurityGroup::class, 'parent_id');
    }

    /**
     * Child security groups relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\SecurityGroup, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(SecurityGroup::class, 'parent_id');
    }

    /**
     * All descendant security groups
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\SecurityGroup, $this>
     */
    public function descendants(): HasMany
    {
        return $this->hasMany(SecurityGroup::class, 'parent_id')
            ->with('descendants');
    }

    /**
     * Get all ancestors of this group
     */
    public function ancestors(): Collection
    {
        if (! $this->path) {
            return new Collection;
        }

        $ancestorIds = explode('/', $this->path);

        return self::whereIn('id', $ancestorIds)->orderBy('level')->get();
    }

    /**
     * Users who are members of this security group
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\User, $this, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'security_group_memberships', 'group_id', 'user_id')
            ->withPivot([
                'is_owner',
                'is_admin',
                'inherit_from_parent',
                'can_manage_members',
                'can_assign_records',
                'permission_overrides',
                'joined_at',
                'added_by',
                'notes',
            ])
            ->withTimestamps();
    }

    /**
     * Group owners
     */
    public function owners(): BelongsToMany
    {
        return $this->members()->wherePivot('is_owner', true);
    }

    /**
     * Group administrators
     */
    public function administrators(): BelongsToMany
    {
        return $this->members()->wherePivot('is_admin', true);
    }

    /**
     * Records that have access controlled by this group
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\SecurityGroupRecordAccess, $this>
     */
    public function recordAccess(): HasMany
    {
        return $this->hasMany(SecurityGroupRecordAccess::class, 'group_id');
    }

    /**
     * Audit logs for this security group
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\SecurityGroupAuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(SecurityGroupAuditLog::class, 'group_id');
    }

    /**
     * Broadcast messages sent to this group
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\SecurityGroupBroadcastMessage, $this>
     */
    public function broadcastMessages(): HasMany
    {
        return $this->hasMany(SecurityGroupBroadcastMessage::class, 'group_id');
    }

    /**
     * Update hierarchy path for this group and all descendants
     */
    public function updateHierarchy(): void
    {
        DB::transaction(function (): void {
            if ($this->parent_id) {
                $parent = static::find($this->parent_id);
                if ($parent) {
                    $this->level = $parent->level + 1;
                    $this->path = $parent->path ? $parent->path . '/' . $parent->id : (string) $parent->id;
                }
            } else {
                $this->level = 0;
                $this->path = null;
            }

            $this->saveQuietly();

            // Update all descendants
            foreach ($this->children as $child) {
                $child->updateHierarchy();
            }
        });
    }

    /**
     * Check if user is a member of this group (including inheritance)
     */
    public function hasMember(User $user, bool $includeInherited = true): bool
    {
        // Direct membership
        if ($this->members()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Check inherited membership from parent groups
        if ($includeInherited && $this->inherit_permissions) {
            foreach ($this->ancestors() as $ancestor) {
                if ($ancestor->members()->where('user_id', $user->id)->exists()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user has specific permission in this group
     */
    public function userHasPermission(User $user, string $permission, ?string $module = null): bool
    {
        $membership = $this->members()->where('user_id', $user->id)->first();

        if (! $membership) {
            // Check inherited permissions from parent groups
            if ($this->inherit_permissions) {
                foreach ($this->ancestors() as $ancestor) {
                    if ($ancestor->userHasPermission($user, $permission, $module)) {
                        return true;
                    }
                }
            }

            return false;
        }

        // Check permission overrides
        $overrides = $membership->pivot->permission_overrides ?? [];
        if (isset($overrides[$permission])) {
            return $overrides[$permission];
        }

        // Check group-level permissions
        $groupPermissions = $this->record_level_permissions ?? [];
        $modulePermissions = $module ? ($groupPermissions[$module] ?? []) : $groupPermissions;

        return $modulePermissions[$permission] ?? false;
    }

    /**
     * Get effective permissions for a user in this group
     */
    public function getEffectivePermissions(User $user): array
    {
        $permissions = [];

        // Start with group-level permissions
        if ($this->record_level_permissions) {
            $permissions = $this->record_level_permissions;
        }

        // Apply inherited permissions from ancestors
        if ($this->inherit_permissions) {
            foreach ($this->ancestors() as $ancestor) {
                $ancestorPermissions = $ancestor->record_level_permissions ?? [];
                $permissions = array_merge($ancestorPermissions, $permissions);
            }
        }

        // Apply user-specific overrides
        $membership = $this->members()->where('user_id', $user->id)->first();
        if ($membership && $membership->pivot->permission_overrides) {
            return array_merge($permissions, $membership->pivot->permission_overrides);
        }

        return $permissions;
    }

    /**
     * Add a user to this security group
     */
    public function addMember(User $user, array $attributes = []): void
    {
        $this->members()->attach($user->id, array_merge([
            'joined_at' => now(),
            'added_by' => auth()->id(),
        ], $attributes));

        // Log the action
        $this->logAudit('member_added', 'membership', $user->id, [], [
            'user_id' => $user->id,
            'attributes' => $attributes,
        ]);
    }

    /**
     * Remove a user from this security group
     */
    public function removeMember(User $user): void
    {
        $this->members()->detach($user->id);

        // Log the action
        $this->logAudit('member_removed', 'membership', $user->id, [
            'user_id' => $user->id,
        ], []);
    }

    /**
     * Grant access to a record for this group
     */
    public function grantRecordAccess($record, string $accessLevel = 'read', array $fieldPermissions = []): SecurityGroupRecordAccess
    {
        $access = SecurityGroupRecordAccess::updateOrCreate([
            'group_id' => $this->id,
            'record_type' => $record::class,
            'record_id' => $record->getKey(),
        ], [
            'access_level' => $accessLevel,
            'field_permissions' => $fieldPermissions,
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
        ]);

        // Log the action
        $this->logAudit('record_access_granted', 'record_access', $access->id, [], [
            'record_type' => $record::class,
            'record_id' => $record->getKey(),
            'access_level' => $accessLevel,
        ]);

        return $access;
    }

    /**
     * Revoke access to a record for this group
     */
    public function revokeRecordAccess($record): void
    {
        $access = SecurityGroupRecordAccess::where([
            'group_id' => $this->id,
            'record_type' => $record::class,
            'record_id' => $record->getKey(),
        ])->first();

        if ($access) {
            $access->delete();

            // Log the action
            $this->logAudit('record_access_revoked', 'record_access', $access->id, [
                'record_type' => $record::class,
                'record_id' => $record->getKey(),
                'access_level' => $access->access_level,
            ], []);
        }
    }

    /**
     * Send a broadcast message to this group
     */
    public function sendBroadcastMessage(string $subject, string $message, array $options = []): SecurityGroupBroadcastMessage
    {
        $broadcastMessage = $this->broadcastMessages()->create(array_merge([
            'sender_id' => auth()->id(),
            'subject' => $subject,
            'message' => $message,
            'status' => 'sent',
            'sent_at' => now(),
        ], $options));

        // Log the action
        $this->logAudit('broadcast_sent', 'broadcast_message', $broadcastMessage->id, [], [
            'subject' => $subject,
            'message_length' => strlen($message),
        ]);

        return $broadcastMessage;
    }

    /**
     * Log an audit event for this security group
     */
    public function logAudit(string $action, ?string $entityType = null, ?int $entityId = null, array $oldValues = [], array $newValues = []): SecurityGroupAuditLog
    {
        return $this->auditLogs()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Scope to only security groups
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function securityGroups(Builder $query): Builder
    {
        return $query->where('is_security_group', true);
    }

    /**
     * Scope to active groups
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Scope to primary groups
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function primary(Builder $query): Builder
    {
        return $query->where('is_primary_group', true);
    }

    /**
     * Scope to root level groups (no parent)
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function rootLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to groups at a specific level
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function atLevel(Builder $query, int $level): Builder
    {
        return $query->where('level', $level);
    }
}
