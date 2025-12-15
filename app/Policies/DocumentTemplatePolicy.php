<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DocumentTemplate;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class DocumentTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, DocumentTemplate $template): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($template);
    }

    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function update(User $user, DocumentTemplate $template): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($template);
    }

    public function delete(User $user, DocumentTemplate $template): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($template);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function restore(User $user, DocumentTemplate $template): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($template);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function forceDelete(User $user, DocumentTemplate $template): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $this->inTenant($template)
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

    private function inTenant(DocumentTemplate $template): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $template->team?->is($tenant);
    }
}
