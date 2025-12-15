<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SecurityGroupAuditLog extends Model
{
    protected $fillable = [
        'group_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'notes',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    /**
     * The security group this audit log belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\SecurityGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(SecurityGroup::class, 'group_id');
    }

    /**
     * The user who performed the action
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get a human-readable description of the action
     */
    protected function getActionDescriptionAttribute(): string
    {
        return match ($this->action) {
            'created' => 'Security group created',
            'updated' => 'Security group updated',
            'deleted' => 'Security group deleted',
            'member_added' => 'Member added to group',
            'member_removed' => 'Member removed from group',
            'member_updated' => 'Member permissions updated',
            'record_access_granted' => 'Record access granted',
            'record_access_revoked' => 'Record access revoked',
            'record_access_updated' => 'Record access updated',
            'broadcast_sent' => 'Broadcast message sent',
            'layout_updated' => 'Group layout updated',
            'permissions_updated' => 'Group permissions updated',
            'hierarchy_changed' => 'Group hierarchy changed',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    /**
     * Get the changes made in this audit log
     */
    protected function getChangesAttribute(): array
    {
        $changes = [];

        if ($this->old_values && $this->new_values) {
            foreach ($this->new_values as $key => $newValue) {
                $oldValue = $this->old_values[$key] ?? null;
                if ($oldValue !== $newValue) {
                    $changes[$key] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
            }
        }

        return $changes;
    }
}
