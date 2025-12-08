<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountTeamAccessLevel;
use App\Enums\AccountTeamRole;
use App\Models\Concerns\HasTeam;
use Database\Factories\AccountTeamMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property AccountTeamRole $role
 * @property AccountTeamAccessLevel $access_level
 */
final class AccountTeamMember extends Model
{
    /** @use HasFactory<AccountTeamMemberFactory> */
    use HasFactory;

    use HasTeam;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'team_id',
        'user_id',
        'role',
        'access_level',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'role' => AccountTeamRole::class,
            'access_level' => AccountTeamAccessLevel::class,
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
