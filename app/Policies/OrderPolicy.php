<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class OrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, Order $order): bool
    {
        return $this->canAccessTenant($user) && $this->orderInTenant($order);
    }

    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function update(User $user, Order $order): bool
    {
        return $this->canAccessTenant($user) && $this->orderInTenant($order);
    }

    public function delete(User $user, Order $order): bool
    {
        return $this->canAccessTenant($user) && $this->orderInTenant($order);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function restore(User $user, Order $order): bool
    {
        return $this->canAccessTenant($user) && $this->orderInTenant($order);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function forceDelete(User $user, Order $order): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $this->orderInTenant($order)
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

    private function orderInTenant(Order $order): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $order->team?->is($tenant);
    }
}
