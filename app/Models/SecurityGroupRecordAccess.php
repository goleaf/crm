<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class SecurityGroupRecordAccess extends Model
{
    protected $fillable = [
        'group_id',
        'record_type',
        'record_id',
        'access_level',
        'field_permissions',
        'inherit_from_parent',
        'permission_overrides',
        'assigned_by',
        'assigned_at',
        'notes',
    ];

    protected $casts = [
        'field_permissions' => 'array',
        'inherit_from_parent' => 'boolean',
        'permission_overrides' => 'array',
        'assigned_at' => 'datetime',
    ];

    /**
     * The security group that has access to the record
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\SecurityGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(SecurityGroup::class, 'group_id');
    }

    /**
     * The record that access is granted to
     */
    public function record(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The user who assigned this access
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Check if the access level allows a specific action
     */
    public function allowsAction(string $action): bool
    {
        return match ($this->access_level) {
            'none' => false,
            'read' => in_array($action, ['view', 'list']),
            'write' => in_array($action, ['view', 'list', 'edit', 'update']),
            'admin' => in_array($action, ['view', 'list', 'edit', 'update', 'delete', 'create']),
            'owner' => true, // Owner can do everything
            default => false,
        };
    }

    /**
     * Check if user has access to a specific field
     */
    public function hasFieldAccess(string $field, string $action = 'view'): bool
    {
        if (! $this->field_permissions) {
            return $this->allowsAction($action);
        }

        $fieldPermission = $this->field_permissions[$field] ?? null;

        if ($fieldPermission === null) {
            return $this->allowsAction($action);
        }

        return match ($fieldPermission) {
            'none' => false,
            'read' => $action === 'view',
            'write' => in_array($action, ['view', 'edit']),
            'admin' => true,
            default => false,
        };
    }
}
