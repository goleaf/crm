<?php

declare(strict_types=1);

namespace App\Models\Admin;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

final class PasswordHistory extends Model
{
    protected $fillable = [
        'user_id',
        'password_hash',
    ];

    protected $hidden = [
        'password_hash',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function forUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function recent($query, int $count)
    {
        return $query->orderBy('created_at', 'desc')->limit($count);
    }

    public static function addPasswordHistory(User $user, string $password): self
    {
        return self::create([
            'user_id' => $user->id,
            'password_hash' => Hash::make($password),
        ]);
    }

    public static function isPasswordReused(User $user, string $password, int $historyCount): bool
    {
        $recentPasswords = self::forUser($user)
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
        $oldPasswords = self::forUser($user)
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
