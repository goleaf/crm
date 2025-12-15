<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class SettingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }

    public function view(User $user, Setting $setting): bool
    {
        // Public settings can be viewed by anyone in a team
        if ($setting->is_public) {
            return $user->hasVerifiedEmail() && $user->currentTeam !== null;
        }

        // Private settings require admin role
        if ($setting->team_id === null) {
            // Global settings require admin role
            return $user->hasTeamRole(Filament::getTenant(), 'admin');
        }

        // Team-specific settings require user to belong to that team
        return $user->belongsToTeam($setting->team);
    }

    public function create(User $user): bool
    {
        // Only admins can create settings
        return $user->hasTeamRole(Filament::getTenant(), 'admin');
    }

    public function update(User $user, Setting $setting): bool
    {
        // Only admins can update settings
        if ($setting->team_id === null) {
            return $user->hasTeamRole(Filament::getTenant(), 'admin');
        }

        return $user->belongsToTeam($setting->team)
            && $user->hasTeamRole($setting->team, 'admin');
    }

    public function delete(User $user, Setting $setting): bool
    {
        // Only admins can delete settings
        if ($setting->team_id === null) {
            return $user->hasTeamRole(Filament::getTenant(), 'admin');
        }

        return $user->belongsToTeam($setting->team)
            && $user->hasTeamRole($setting->team, 'admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasTeamRole(Filament::getTenant(), 'admin');
    }

    public function restore(User $user): bool
    {
        return $user->hasTeamRole(Filament::getTenant(), 'admin');
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasTeamRole(Filament::getTenant(), 'admin');
    }

    public function forceDelete(User $user): bool
    {
        return $user->hasTeamRole(Filament::getTenant(), 'admin');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasTeamRole(Filament::getTenant(), 'admin');
    }
}
