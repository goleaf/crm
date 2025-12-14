<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int            $id
 * @property int            $role_id
 * @property string         $action
 * @property array          $changes
 * @property int|null       $user_id
 * @property string|null    $ip_address
 * @property string|null    $user_agent
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class RoleAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id',
        'action',
        'changes',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
