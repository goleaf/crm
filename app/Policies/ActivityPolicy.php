<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class ActivityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, Activity $activity): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($activity);
    }

    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function update(User $user, Activity $activity): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($activity);
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($activity);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function restore(User $user, Activity $activity): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($activity);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function forceDelete(User $user, Activity $activity): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $this->inTenant($activity)
            && $user->hasTeamRole($tenant, 'admin');
    }

    public function forceDeleteAny(User $user): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $user->hasTeamRole($tenant, 'admin');
    }

    private function canAccessTenant(User $user): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $user->hasVerifiedEmail()
            && $user->currentTeam !== null
            && $user->belongsToTeam($tenant);
    }

    private function inTenant(Activity $activity): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $activity->team?->is($tenant);
    }
}
