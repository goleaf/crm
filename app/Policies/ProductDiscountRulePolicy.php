<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProductDiscountRule;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class ProductDiscountRulePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, ProductDiscountRule $rule): bool
    {
        return $this->canAccessTenant($user) && $this->ruleInTenant($rule);
    }

    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function update(User $user, ProductDiscountRule $rule): bool
    {
        return $this->canAccessTenant($user) && $this->ruleInTenant($rule);
    }

    public function delete(User $user, ProductDiscountRule $rule): bool
    {
        return $this->canAccessTenant($user) && $this->ruleInTenant($rule);
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

    private function ruleInTenant(ProductDiscountRule $rule): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $rule->team?->is($tenant);
    }
}

