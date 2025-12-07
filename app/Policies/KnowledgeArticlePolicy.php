<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Knowledge\ArticleVisibility;
use App\Models\KnowledgeArticle;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class KnowledgeArticlePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }

    public function view(User $user, KnowledgeArticle $article): bool
    {
        if ($article->visibility === ArticleVisibility::PUBLIC) {
            return true;
        }

        return $user->belongsToTeam($article->team);
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }

    public function update(User $user, KnowledgeArticle $article): bool
    {
        return $user->belongsToTeam($article->team);
    }

    public function delete(User $user, KnowledgeArticle $article): bool
    {
        return $user->belongsToTeam($article->team);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->currentTeam !== null;
    }

    public function restore(User $user, KnowledgeArticle $article): bool
    {
        return $user->belongsToTeam($article->team);
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
