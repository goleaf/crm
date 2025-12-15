<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class LeadPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, Lead $lead): bool
    {
        return $this->canAccessTenant($user) && $this->leadInTenant($lead);
    }

    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->canAccessTenant($user) && $this->leadInTenant($lead);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $this->canAccessTenant($user) && $this->leadInTenant($lead);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function restore(User $user, Lead $lead): bool
    {
        return $this->canAccessTenant($user) && $this->leadInTenant($lead);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function forceDelete(User $user, Lead $lead): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $this->leadInTenant($lead)
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

    private function leadInTenant(Lead $lead): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $lead->team?->is($tenant);
    }
}
