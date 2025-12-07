<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProductAttribute;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class ProductAttributePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, ProductAttribute $attribute): bool
    {
        return $this->canAccessTenant($user) && $this->attributeInTenant($attribute);
    }

    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function update(User $user, ProductAttribute $attribute): bool
    {
        return $this->canAccessTenant($user) && $this->attributeInTenant($attribute);
    }

    public function delete(User $user, ProductAttribute $attribute): bool
    {
        return $this->canAccessTenant($user) && $this->attributeInTenant($attribute);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function restore(User $user, ProductAttribute $attribute): bool
    {
        return $this->canAccessTenant($user) && $this->attributeInTenant($attribute);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function forceDelete(User $user, ProductAttribute $attribute): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $this->attributeInTenant($attribute)
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

    private function attributeInTenant(ProductAttribute $attribute): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $attribute->team?->is($tenant);
    }
}
