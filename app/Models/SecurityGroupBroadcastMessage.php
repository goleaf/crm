<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SecurityGroupBroadcastMessage extends Model
{
    protected $fillable = [
        'group_id',
        'sender_id',
        'subject',
        'message',
        'priority',
        'include_subgroups',
        'require_acknowledgment',
        'scheduled_at',
        'status',
        'sent_at',
        'delivery_stats',
        'metadata',
    ];

    protected $casts = [
        'include_subgroups' => 'boolean',
        'require_acknowledgment' => 'boolean',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivery_stats' => 'array',
        'metadata' => 'array',
    ];

    /**
     * The security group this message was sent to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\SecurityGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(SecurityGroup::class, 'group_id');
    }

    /**
     * The user who sent this message
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Acknowledgments for this message
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\SecurityGroupMessageAcknowledgment, $this>
     */
    public function acknowledgments(): HasMany
    {
        return $this->hasMany(SecurityGroupMessageAcknowledgment::class, 'message_id');
    }

    /**
     * Get the priority color for display
     */
    protected function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'gray',
            'normal' => 'primary',
            'high' => 'warning',
            'urgent' => 'danger',
            default => 'primary',
        };
    }

    /**
     * Get the status color for display
     */
    protected function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'scheduled' => 'warning',
            'sent' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Check if the message is scheduled for future delivery
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled' && $this->scheduled_at && $this->scheduled_at->isFuture();
    }

    /**
     * Check if the message has been sent
     */
    public function isSent(): bool
    {
        return $this->status === 'sent' && $this->sent_at;
    }

    /**
     * Get delivery statistics
     */
    protected function getDeliveryStatsAttribute($value): array
    {
        $stats = json_decode((string) $value, true) ?? [];

        return array_merge([
            'total_recipients' => 0,
            'delivered' => 0,
            'acknowledged' => 0,
            'failed' => 0,
        ], $stats);
    }

    /**
     * Update delivery statistics
     */
    public function updateDeliveryStats(): void
    {
        $totalRecipients = $this->getTotalRecipients();
        $acknowledged = $this->acknowledgments()->count();

        $this->update([
            'delivery_stats' => [
                'total_recipients' => $totalRecipients,
                'delivered' => $totalRecipients, // Assume all delivered for now
                'acknowledged' => $acknowledged,
                'failed' => 0,
                'acknowledgment_rate' => $totalRecipients > 0 ? round(($acknowledged / $totalRecipients) * 100, 2) : 0,
            ],
        ]);
    }

    /**
     * Get total number of recipients for this message
     */
    public function getTotalRecipients(): int
    {
        $recipients = collect();

        // Add direct group members
        $recipients = $recipients->merge($this->group->members->pluck('id'));

        // Add subgroup members if included
        if ($this->include_subgroups) {
            foreach ($this->group->descendants as $subgroup) {
                $recipients = $recipients->merge($subgroup->members->pluck('id'));
            }
        }

        return $recipients->unique()->count();
    }

    /**
     * Send the message (mark as sent)
     */
    public function send(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->updateDeliveryStats();

        // Log the action
        $this->group->logAudit('broadcast_sent', 'broadcast_message', $this->id, [], [
            'subject' => $this->subject,
            'total_recipients' => $this->getTotalRecipients(),
        ]);
    }

    /**
     * Cancel the message
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);

        // Log the action
        $this->group->logAudit('broadcast_cancelled', 'broadcast_message', $this->id, [
            'status' => 'scheduled',
        ], [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Scope to sent messages
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function sent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope to scheduled messages
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function scheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope to messages requiring acknowledgment
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function requiringAcknowledgment(Builder $query): Builder
    {
        return $query->where('require_acknowledgment', true);
    }
}
