<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SearchHistory;
use Illuminate\Auth\Access\HandlesAuthorization;

class SearchHistoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SearchHistory');
    }

    public function view(AuthUser $authUser, SearchHistory $searchHistory): bool
    {
        return $authUser->can('View:SearchHistory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SearchHistory');
    }

    public function update(AuthUser $authUser, SearchHistory $searchHistory): bool
    {
        return $authUser->can('Update:SearchHistory');
    }

    public function delete(AuthUser $authUser, SearchHistory $searchHistory): bool
    {
        return $authUser->can('Delete:SearchHistory');
    }

    public function restore(AuthUser $authUser, SearchHistory $searchHistory): bool
    {
        return $authUser->can('Restore:SearchHistory');
    }

    public function forceDelete(AuthUser $authUser, SearchHistory $searchHistory): bool
    {
        return $authUser->can('ForceDelete:SearchHistory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SearchHistory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SearchHistory');
    }

    public function replicate(AuthUser $authUser, SearchHistory $searchHistory): bool
    {
        return $authUser->can('Replicate:SearchHistory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SearchHistory');
    }

}