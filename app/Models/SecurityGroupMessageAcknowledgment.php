<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SecurityGroupMessageAcknowledgment extends Model
{
    protected $fillable = [
        'message_id',
        'user_id',
        'acknowledged_at',
        'response',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];

    /**
     * The broadcast message this acknowledgment belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\SecurityGroupBroadcastMessage, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(SecurityGroupBroadcastMessage::class, 'message_id');
    }

    /**
     * The user who acknowledged the message
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
