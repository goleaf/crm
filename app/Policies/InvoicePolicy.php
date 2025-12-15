<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class InvoicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->canAccessTenant($user) && $this->invoiceInTenant($invoice);
    }

    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->canAccessTenant($user) && $this->invoiceInTenant($invoice);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $this->canAccessTenant($user) && $this->invoiceInTenant($invoice);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return $this->canAccessTenant($user) && $this->invoiceInTenant($invoice);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $this->invoiceInTenant($invoice)
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

    private function invoiceInTenant(Invoice $invoice): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $invoice->team?->is($tenant);
    }
}
