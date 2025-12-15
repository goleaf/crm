<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Delivery;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class DeliveryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, Delivery $delivery): bool
    {
        return $this->canAccessTenant($user) && $this->deliveryInTenant($delivery);
    }

    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function update(User $user, Delivery $delivery): bool
    {
        return $this->canAccessTenant($user) && $this->deliveryInTenant($delivery);
    }

    public function delete(User $user, Delivery $delivery): bool
    {
        return $this->canAccessTenant($user) && $this->deliveryInTenant($delivery);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function restore(User $user, Delivery $delivery): bool
    {
        return $this->canAccessTenant($user) && $this->deliveryInTenant($delivery);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function forceDelete(User $user, Delivery $delivery): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $this->deliveryInTenant($delivery)
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

    private function deliveryInTenant(Delivery $delivery): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $delivery->team?->is($tenant);
    }
}
