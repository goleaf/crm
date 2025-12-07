<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProductAttributeValue;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class ProductAttributeValuePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, ProductAttributeValue $value): bool
    {
        return $this->canAccessTenant($user) && $this->valueInTenant($value);
    }

    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function update(User $user, ProductAttributeValue $value): bool
    {
        return $this->canAccessTenant($user) && $this->valueInTenant($value);
    }

    public function delete(User $user, ProductAttributeValue $value): bool
    {
        return $this->canAccessTenant($user) && $this->valueInTenant($value);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function restore(User $user, ProductAttributeValue $value): bool
    {
        return $this->canAccessTenant($user) && $this->valueInTenant($value);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function forceDelete(User $user, ProductAttributeValue $value): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $this->valueInTenant($value)
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

    private function valueInTenant(ProductAttributeValue $value): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $value->attribute?->team?->is($tenant);
    }
}
