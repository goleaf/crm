<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\NoteVisibility;
use App\Models\Note;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class NotePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }

    public function view(User $user, Note $note): bool
    {
        return $user->belongsToTeam($note->team)
            && $this->canAccessVisibility($user, $note);
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }

    public function update(User $user, Note $note): bool
    {
        return $user->belongsToTeam($note->team)
            && $this->canAccessVisibility($user, $note);
    }

    public function delete(User $user, Note $note): bool
    {
        return $user->belongsToTeam($note->team)
            && $this->canAccessVisibility($user, $note);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }

    public function restore(User $user, Note $note): bool
    {
        return $user->belongsToTeam($note->team)
            && $this->canAccessVisibility($user, $note);
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

    private function canAccessVisibility(User $user, Note $note): bool
    {
        if ($note->visibility !== NoteVisibility::PRIVATE) {
            return true;
        }
        if ($note->creator_id === $user->getKey()) {
            return true;
        }

        return $user->hasTeamRole(Filament::getTenant(), 'admin');
    }
}
