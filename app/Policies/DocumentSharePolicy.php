<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DocumentShare;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class DocumentSharePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, DocumentShare $share): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($share);
    }

    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function update(User $user, DocumentShare $share): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($share);
    }

    public function delete(User $user, DocumentShare $share): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($share);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function restore(User $user, DocumentShare $share): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($share);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function forceDelete(User $user, DocumentShare $share): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $this->inTenant($share)
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

    private function inTenant(DocumentShare $share): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $share->team_id === $tenant->getKey();
    }
}
