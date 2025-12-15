<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DocumentVersion;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class DocumentVersionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, DocumentVersion $version): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($version);
    }

    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function update(User $user, DocumentVersion $version): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($version);
    }

    public function delete(User $user, DocumentVersion $version): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($version);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function restore(User $user, DocumentVersion $version): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($version);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function forceDelete(User $user, DocumentVersion $version): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $this->inTenant($version)
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

    private function inTenant(DocumentVersion $version): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $version->team_id === $tenant->getKey();
    }
}
