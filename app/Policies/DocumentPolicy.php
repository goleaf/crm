<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class DocumentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, Document $document): bool
    {
        return $this->canAccessTenant($user) && $this->documentInTenant($document);
    }

    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function update(User $user, Document $document): bool
    {
        return $this->canAccessTenant($user) && $this->documentInTenant($document);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->canAccessTenant($user) && $this->documentInTenant($document);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function restore(User $user, Document $document): bool
    {
        return $this->canAccessTenant($user) && $this->documentInTenant($document);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function forceDelete(User $user, Document $document): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $this->documentInTenant($document)
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

    private function documentInTenant(Document $document): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $document->team?->is($tenant);
    }
}
