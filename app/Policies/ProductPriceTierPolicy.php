<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProductPriceTier;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class ProductPriceTierPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, ProductPriceTier $tier): bool
    {
        return $this->canAccessTenant($user) && $this->tierInTenant($tier);
    }

    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function update(User $user, ProductPriceTier $tier): bool
    {
        return $this->canAccessTenant($user) && $this->tierInTenant($tier);
    }

    public function delete(User $user, ProductPriceTier $tier): bool
    {
        return $this->canAccessTenant($user) && $this->tierInTenant($tier);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    private function canAccessTenant(User $user): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $user->hasVerifiedEmail()
            && $user->currentTeam !== null
            && $user->belongsToTeam($tenant);
    }

    private function tierInTenant(ProductPriceTier $tier): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $tier->team?->is($tenant);
    }
}

