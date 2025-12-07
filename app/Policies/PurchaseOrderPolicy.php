<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class PurchaseOrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($purchaseOrder);
    }

    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($purchaseOrder);
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($purchaseOrder);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function restore(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($purchaseOrder);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function forceDelete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $this->inTenant($purchaseOrder)
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

    private function inTenant(PurchaseOrder $purchaseOrder): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $purchaseOrder->team?->is($tenant);
    }
}
