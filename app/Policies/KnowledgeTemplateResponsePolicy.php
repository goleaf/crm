<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\KnowledgeTemplateResponse;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class KnowledgeTemplateResponsePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }

    public function view(User $user, KnowledgeTemplateResponse $template): bool
    {
        return $user->belongsToTeam($template->team);
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }

    public function update(User $user, KnowledgeTemplateResponse $template): bool
    {
        return $user->belongsToTeam($template->team);
    }

    public function delete(User $user, KnowledgeTemplateResponse $template): bool
    {
        return $user->belongsToTeam($template->team);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }

    public function restore(User $user, KnowledgeTemplateResponse $template): bool
    {
        return $user->belongsToTeam($template->team);
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }

    public function forceDelete(User $user): bool
    {
        return $user->hasTeamRole(Filament::getTenant(), 'admin');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasTeamRole(Filament::getTenant(), 'admin');
    }
}
