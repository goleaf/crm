<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Account;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class AccountPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, Account $account): bool
    {
        return $this->canAccessTenant($user) && $this->accountInTenant($account);
    }

    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function update(User $user, Account $account): bool
    {
        return $this->canAccessTenant($user) && $this->accountInTenant($account);
    }

    public function delete(User $user, Account $account): bool
    {
        return $this->canAccessTenant($user) && $this->accountInTenant($account);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function restore(User $user, Account $account): bool
    {
        return $this->canAccessTenant($user) && $this->accountInTenant($account);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function forceDelete(User $user, Account $account): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $this->accountInTenant($account)
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

    private function accountInTenant(Account $account): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $account->team?->is($tenant);
    }
}
