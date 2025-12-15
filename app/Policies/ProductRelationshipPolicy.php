<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProductRelationship;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class ProductRelationshipPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, ProductRelationship $relationship): bool
    {
        return $this->canAccessTenant($user) && $this->relationshipInTenant($relationship);
    }

    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function update(User $user, ProductRelationship $relationship): bool
    {
        return $this->canAccessTenant($user) && $this->relationshipInTenant($relationship);
    }

    public function delete(User $user, ProductRelationship $relationship): bool
    {
        return $this->canAccessTenant($user) && $this->relationshipInTenant($relationship);
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

    private function relationshipInTenant(ProductRelationship $relationship): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $relationship->team?->is($tenant);
    }
}

