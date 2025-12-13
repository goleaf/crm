<?php

namespace App\Models\Admin;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'last_activity',
        'is_active',
    ];

    protected $casts = [
        'last_activity' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeExpired($query, int $minutes = 120)
    {
        return $query->where('last_activity', '<', now()->subMinutes($minutes));
    }

    public function terminate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    public function updateActivity(): bool
    {
        return $this->update(['last_activity' => now()]);
    }

    public static function createSession(User $user, string $sessionId): self
    {
        return static::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'last_activity' => now(),
            'is_active' => true,
        ]);
    }

    public static function terminateUserSessions(User $user, ?string $exceptSessionId = null): int
    {
        $query = static::where('user_id', $user->id)->where('is_active', true);
        
        if ($exceptSessionId) {
            $query->where('session_id', '!=', $exceptSessionId);
        }
        
        return $query->update(['is_active' => false]);
    }
}