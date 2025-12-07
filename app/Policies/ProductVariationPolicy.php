<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProductVariation;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class ProductVariationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, ProductVariation $variation): bool
    {
        return $this->canAccessTenant($user) && $this->variationInTenant($variation);
    }

    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function update(User $user, ProductVariation $variation): bool
    {
        return $this->canAccessTenant($user) && $this->variationInTenant($variation);
    }

    public function delete(User $user, ProductVariation $variation): bool
    {
        return $this->canAccessTenant($user) && $this->variationInTenant($variation);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function restore(User $user, ProductVariation $variation): bool
    {
        return $this->canAccessTenant($user) && $this->variationInTenant($variation);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function forceDelete(User $user, ProductVariation $variation): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $this->variationInTenant($variation)
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

    private function variationInTenant(ProductVariation $variation): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $variation->product?->team?->is($tenant);
    }
}
