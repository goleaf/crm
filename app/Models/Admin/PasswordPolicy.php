<?php

declare(strict_types=1);

namespace App\Models\Admin;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PasswordPolicy extends Model
{
    protected $fillable = [
        'name',
        'min_length',
        'max_length',
        'require_uppercase',
        'require_lowercase',
        'require_numbers',
        'require_symbols',
        'password_history_count',
        'max_age_days',
        'lockout_attempts',
        'lockout_duration_minutes',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'require_uppercase' => 'boolean',
        'require_lowercase' => 'boolean',
        'require_numbers' => 'boolean',
        'require_symbols' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function validatePassword(string $password): array
    {
        $errors = [];

        if (strlen($password) < $this->min_length) {
            $errors[] = __('app.validation.password_min_length', ['min' => $this->min_length]);
        }

        if (strlen($password) > $this->max_length) {
            $errors[] = __('app.validation.password_max_length', ['max' => $this->max_length]);
        }

        if ($this->require_uppercase && ! preg_match('/[A-Z]/', $password)) {
            $errors[] = __('app.validation.password_require_uppercase');
        }

        if ($this->require_lowercase && ! preg_match('/[a-z]/', $password)) {
            $errors[] = __('app.validation.password_require_lowercase');
        }

        if ($this->require_numbers && ! preg_match('/\d/', $password)) {
            $errors[] = __('app.validation.password_require_numbers');
        }

        if ($this->require_symbols && ! preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = __('app.validation.password_require_symbols');
        }

        return $errors;
    }

    public function isPasswordExpired(User $user): bool
    {
        if (! $this->max_age_days || ! $user->password_expires_at) {
            return false;
        }

        return $user->password_expires_at->isPast();
    }

    public static function getDefault(): ?self
    {
        return self::where('is_default', true)->where('is_active', true)->first();
    }
}
