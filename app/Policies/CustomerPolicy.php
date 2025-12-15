<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class CustomerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->canAccessTenant($user) && $this->customerInTenant($customer);
    }

    public function create(): bool
    {
        return false;
        // Read-only view
    }

    public function update(): bool
    {
        return false;
        // Read-only view
    }

    public function delete(): bool
    {
        return false;
        // Read-only view
    }

    public function deleteAny(): bool
    {
        return false;
        // Read-only view
    }

    public function restore(): bool
    {
        return false;
        // Read-only view
    }

    public function restoreAny(): bool
    {
        return false;
        // Read-only view
    }

    public function forceDelete(): bool
    {
        return false;
        // Read-only view
    }

    public function forceDeleteAny(): bool
    {
        return false;
        // Read-only view
    }

    private function canAccessTenant(User $user): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $user->hasVerifiedEmail()
            && $user->currentTeam !== null
            && $user->belongsToTeam($tenant);
    }

    private function customerInTenant(Customer $customer): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $customer->team?->is($tenant);
    }
}
