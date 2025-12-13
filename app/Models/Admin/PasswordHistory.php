<?php

namespace App\Models\Admin;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class PasswordHistory extends Model
{
    protected $fillable = [
        'user_id',
        'password_hash',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeRecent($query, int $count)
    {
        return $query->orderBy('created_at', 'desc')->limit($count);
    }

    public static function addPasswordHistory(User $user, string $password): self
    {
        return static::create([
            'user_id' => $user->id,
            'password_hash' => Hash::make($password),
        ]);
    }

    public static function isPasswordReused(User $user, string $password, int $historyCount): bool
    {
        $recentPasswords = static::forUser($user)
            ->recent($historyCount)
            ->get();

        foreach ($recentPasswords as $history) {
            if (Hash::check($password, $history->password_hash)) {
                return true;
            }
        }

        return false;
    }

    public static function cleanupOldPasswords(User $user, int $keepCount): int
    {
        $oldPasswords = static::forUser($user)
            ->orderBy('created_at', 'desc')
            ->skip($keepCount)
            ->get();

        $deleted = 0;
        foreach ($oldPasswords as $password) {
            $password->delete();
            $deleted++;
        }

        return $deleted;
    }
}