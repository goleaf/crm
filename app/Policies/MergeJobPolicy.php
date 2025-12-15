<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MergeJob;
use Illuminate\Auth\Access\HandlesAuthorization;

class MergeJobPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MergeJob');
    }

    public function view(AuthUser $authUser, MergeJob $mergeJob): bool
    {
        return $authUser->can('View:MergeJob');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MergeJob');
    }

    public function update(AuthUser $authUser, MergeJob $mergeJob): bool
    {
        return $authUser->can('Update:MergeJob');
    }

    public function delete(AuthUser $authUser, MergeJob $mergeJob): bool
    {
        return $authUser->can('Delete:MergeJob');
    }

    public function restore(AuthUser $authUser, MergeJob $mergeJob): bool
    {
        return $authUser->can('Restore:MergeJob');
    }

    public function forceDelete(AuthUser $authUser, MergeJob $mergeJob): bool
    {
        return $authUser->can('ForceDelete:MergeJob');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MergeJob');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MergeJob');
    }

    public function replicate(AuthUser $authUser, MergeJob $mergeJob): bool
    {
        return $authUser->can('Replicate:MergeJob');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MergeJob');
    }

}