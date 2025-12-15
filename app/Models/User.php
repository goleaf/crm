<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasProfilePhoto;
use App\Models\Concerns\InteractsWithReactions;
use App\Support\PersonNameFormatter;
use Database\Factories\UserFactory;
use Exception;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use HosmelQ\NameOfPerson\PersonName;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Zap\Models\Concerns\HasSchedules;

/**
 * @property string      $name
 * @property string      $email
 * @property string|null $password
 * @property string|null $profile_photo_path
 * @property-read string $profile_photo_url
 * @property Carbon|null $email_verified_at
 * @property string|null $remember_token
 * @property string|null $timezone
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_secret
 */
final class User extends Authenticatable implements FilamentUser, HasAvatar, HasDefaultTenant, HasTenants, MustVerifyEmail
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use HasRoles;
    use HasSchedules;
    use HasTeams;
    use Impersonate;
    use InteractsWithReactions;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
        'user_type',
        'status',
        'password_expires_at',
        'last_login_at',
        'failed_login_attempts',
        'locked_until',
        'password_policy_id',
        'force_password_change',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'profile_photo_url', // @phpstan-ignore rules.modelAppends
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'force_password_change' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'two_factor_recovery_codes' => 'array',
        ];
    }

    protected function getPersonNameAttribute(): ?PersonName
    {
        return PersonNameFormatter::make($this->name);
    }

    /**
     * @return HasMany<UserSocialAccount, $this>
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(UserSocialAccount::class);
    }

    /**
     * @return HasOne<NotificationPreference, $this>
     */
    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function ensureNotificationPreference(): NotificationPreference
    {
        return $this->notificationPreference()->firstOrCreate([], [
            'in_app' => true,
            'email' => true,
            'realtime' => true,
            'activity_alerts' => true,
        ]);
    }

    /**
     * @return BelongsToMany<Task, $this>
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class);
    }

    /**
     * @return HasMany<SupportCase, $this>
     */
    public function assignedCases(): HasMany
    {
        return $this->hasMany(SupportCase::class, 'assigned_to_id');
    }

    /**
     * @return HasMany<AccountTeamMember, $this>
     */
    public function accountTeamMemberships(): HasMany
    {
        return $this->hasMany(AccountTeamMember::class);
    }

    /**
     * @return HasMany<Opportunity, $this>
     */
    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'creator_id');
    }

    /**
     * @return HasMany<SavedSearch, $this>
     */
    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class);
    }

    /**
     * @return BelongsToMany<Opportunity, $this>
     */
    public function collaboratingOpportunities(): BelongsToMany
    {
        return $this->belongsToMany(Opportunity::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function getDefaultTenant(Panel $panel): ?EloquentModel
    {
        return $this->currentTeam;
    }

    /**
     * @throws Exception
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'app';
    }

    /**
     * @return Collection<int, Team>
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->allTeams();
    }

    public function canAccessTenant(EloquentModel $tenant): bool
    {
        return $this->belongsToTeam($tenant);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Admin\PasswordPolicy, $this>
     */
    public function passwordPolicy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin\PasswordPolicy::class);
    }

    /**
     * @return HasMany<\App\Models\Admin\LoginHistory, $this>
     */
    public function loginHistories(): HasMany
    {
        return $this->hasMany(\App\Models\Admin\LoginHistory::class);
    }

    /**
     * @return HasMany<\App\Models\Admin\UserActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(\App\Models\Admin\UserActivity::class);
    }

    /**
     * @return HasMany<\App\Models\Admin\UserSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(\App\Models\Admin\UserSession::class);
    }

    /**
     * @return HasMany<\App\Models\Admin\PasswordHistory, $this>
     */
    public function passwordHistories(): HasMany
    {
        return $this->hasMany(\App\Models\Admin\PasswordHistory::class);
    }
}
