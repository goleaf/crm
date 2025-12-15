<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Quote;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class QuotePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, Quote $quote): bool
    {
        return $this->canAccessTenant($user) && $this->quoteInTenant($quote);
    }

    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function update(User $user, Quote $quote): bool
    {
        return $this->canAccessTenant($user) && $this->quoteInTenant($quote);
    }

    public function delete(User $user, Quote $quote): bool
    {
        return $this->canAccessTenant($user) && $this->quoteInTenant($quote);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function restore(User $user, Quote $quote): bool
    {
        return $this->canAccessTenant($user) && $this->quoteInTenant($quote);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function forceDelete(User $user, Quote $quote): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $this->quoteInTenant($quote)
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

    private function quoteInTenant(Quote $quote): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $quote->team?->is($tenant);
    }
}
