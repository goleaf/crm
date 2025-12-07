<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class ProductPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, Product $product): bool
    {
        return $this->canAccessTenant($user) && $this->productInTenant($product);
    }

    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->canAccessTenant($user) && $this->productInTenant($product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->canAccessTenant($user) && $this->productInTenant($product);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function restore(User $user, Product $product): bool
    {
        return $this->canAccessTenant($user) && $this->productInTenant($product);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function forceDelete(User $user, Product $product): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $this->productInTenant($product)
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

    private function productInTenant(Product $product): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $product->team?->is($tenant);
    }
}
