<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SupportCase;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class SupportCasePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }

    public function view(User $user, SupportCase $case): bool
    {
        return $user->belongsToTeam($case->team);
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }

    public function update(User $user, SupportCase $case): bool
    {
        return $user->belongsToTeam($case->team);
    }

    public function delete(User $user, SupportCase $case): bool
    {
        return $user->belongsToTeam($case->team);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }

    public function restore(User $user, SupportCase $case): bool
    {
        return $user->belongsToTeam($case->team);
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }

    public function forceDelete(User $user, SupportCase $case): bool
    {
        return $user->hasTeamRole($case->team, 'admin');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasTeamRole(Filament::getTenant(), 'admin');
    }
}
