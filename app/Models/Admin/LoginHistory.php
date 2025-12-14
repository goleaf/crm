<?php

declare(strict_types=1);

namespace App\Models\Admin;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LoginHistory extends Model
{
    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'location',
        'device',
        'successful',
        'failure_reason',
        'attempted_at',
    ];

    protected $casts = [
        'successful' => 'boolean',
        'attempted_at' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function successful($query)
    {
        return $query->where('successful', true);
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function failed($query)
    {
        return $query->where('successful', false);
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function forUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function fromIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function recent($query, int $hours = 24)
    {
        return $query->where('attempted_at', '>=', now()->subHours($hours));
    }

    public static function recordLogin(User $user, array $data): self
    {
        return self::create([
            'user_id' => $user->id,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'location' => $data['location'] ?? null,
            'device' => $data['device'] ?? null,
            'successful' => $data['successful'] ?? true,
            'failure_reason' => $data['failure_reason'] ?? null,
            'attempted_at' => now(),
        ]);
    }
}
