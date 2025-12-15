<?php

declare(strict_types=1);

namespace App\Policies;

use Grazulex\ShareLink\Models\ShareLink;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class ShareLinkPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ShareLink');
    }

    public function view(AuthUser $authUser, ShareLink $shareLink): bool
    {
        return $authUser->can('View:ShareLink');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ShareLink');
    }

    public function update(AuthUser $authUser, ShareLink $shareLink): bool
    {
        return $authUser->can('Update:ShareLink');
    }

    public function delete(AuthUser $authUser, ShareLink $shareLink): bool
    {
        return $authUser->can('Delete:ShareLink');
    }

    public function restore(AuthUser $authUser, ShareLink $shareLink): bool
    {
        return $authUser->can('Restore:ShareLink');
    }

    public function forceDelete(AuthUser $authUser, ShareLink $shareLink): bool
    {
        return $authUser->can('ForceDelete:ShareLink');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ShareLink');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ShareLink');
    }

    public function replicate(AuthUser $authUser, ShareLink $shareLink): bool
    {
        return $authUser->can('Replicate:ShareLink');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ShareLink');
    }
}
