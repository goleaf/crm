<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property int                 $id
 * @property string              $name
 * @property string              $guard_name
 * @property string|null         $display_name
 * @property string|null         $description
 * @property bool                $is_template
 * @property bool                $is_admin_role
 * @property bool                $is_studio_role
 * @property int|null            $parent_role_id
 * @property int|null            $team_id
 * @property array|null          $metadata
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class Role extends SpatieRole
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'guard_name',
        'display_name',
        'description',
        'is_template',
        'is_admin_role',
        'is_studio_role',
        'parent_role_id',
        'team_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_template' => 'boolean',
            'is_admin_role' => 'boolean',
            'is_studio_role' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function parentRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'parent_role_id');
    }

    /**
     * @return HasMany<Role, $this>
     */
    public function childRoles(): HasMany
    {
        return $this->hasMany(Role::class, 'parent_role_id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'model_has_roles', 'role_id', 'model_id')
            ->where('model_type', User::class);
    }

    /**
     * @return HasMany<RoleAuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(RoleAuditLog::class);
    }

    /**
     * Get all permissions including inherited ones from parent roles
     */
    public function getAllPermissions(): \Illuminate\Support\Collection
    {
        $permissions = $this->permissions;

        if ($this->parent_role_id && $this->parentRole) {
            $permissions = $permissions->merge($this->parentRole->getAllPermissions());
        }

        return $permissions->unique('id');
    }

    /**
     * Check if this role has circular inheritance
     */
    public function hasCircularInheritance(): bool
    {
        $visited = [];
        $current = $this;

        while ($current && $current->parent_role_id) {
            if (in_array($current->id, $visited, true)) {
                return true;
            }
            $visited[] = $current->id;
            $current = $current->parentRole;
        }

        return false;
    }

    /**
     * Create a role from a template
     */
    public static function createFromTemplate(Role $template, array $attributes = []): Role
    {
        $role = self::create(array_merge([
            'name' => $attributes['name'] ?? $template->name . '_copy',
            'guard_name' => $template->guard_name,
            'display_name' => $attributes['display_name'] ?? $template->display_name,
            'description' => $attributes['description'] ?? $template->description,
            'is_template' => false,
            'is_admin_role' => $template->is_admin_role,
            'is_studio_role' => $template->is_studio_role,
            'team_id' => $attributes['team_id'] ?? $template->team_id,
            'metadata' => $template->metadata,
        ], $attributes));

        // Copy permissions
        $role->syncPermissions($template->permissions);

        return $role;
    }

    /**
     * Log role changes for audit trail
     */
    public function logChange(string $action, array $changes = [], ?User $user = null): void
    {
        $this->auditLogs()->create([
            'action' => $action,
            'changes' => $changes,
            'user_id' => $user?->id ?? auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Scope to templates only
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function templates($query)
    {
        return $query->where('is_template', true);
    }

    /**
     * Scope to non-templates only
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function nonTemplates($query)
    {
        return $query->where('is_template', false);
    }

    /**
     * Scope to admin roles
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function adminRoles($query)
    {
        return $query->where('is_admin_role', true);
    }

    /**
     * Scope to studio roles
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function studioRoles($query)
    {
        return $query->where('is_studio_role', true);
    }

    /**
     * Scope to team roles
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function forTeam($query, ?int $teamId = null)
    {
        return $query->where('team_id', $teamId ?? filament()->getTenant()?->id);
    }
}
