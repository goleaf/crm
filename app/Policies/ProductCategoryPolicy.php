<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProductCategory;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class ProductCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, ProductCategory $category): bool
    {
        return $this->canAccessTenant($user) && $this->categoryInTenant($category);
    }

    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function update(User $user, ProductCategory $category): bool
    {
        return $this->canAccessTenant($user) && $this->categoryInTenant($category);
    }

    public function delete(User $user, ProductCategory $category): bool
    {
        return $this->canAccessTenant($user) && $this->categoryInTenant($category);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function restore(User $user, ProductCategory $category): bool
    {
        return $this->canAccessTenant($user) && $this->categoryInTenant($category);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function forceDelete(User $user, ProductCategory $category): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $this->categoryInTenant($category)
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

    private function categoryInTenant(ProductCategory $category): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $category->team?->is($tenant);
    }
}
