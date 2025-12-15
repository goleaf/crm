<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CuratorMedia;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class CuratorMediaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CuratorMedia');
    }

    public function view(AuthUser $authUser, CuratorMedia $curatorMedia): bool
    {
        return $authUser->can('View:CuratorMedia');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CuratorMedia');
    }

    public function update(AuthUser $authUser, CuratorMedia $curatorMedia): bool
    {
        return $authUser->can('Update:CuratorMedia');
    }

    public function delete(AuthUser $authUser, CuratorMedia $curatorMedia): bool
    {
        return $authUser->can('Delete:CuratorMedia');
    }

    public function restore(AuthUser $authUser, CuratorMedia $curatorMedia): bool
    {
        return $authUser->can('Restore:CuratorMedia');
    }

    public function forceDelete(AuthUser $authUser, CuratorMedia $curatorMedia): bool
    {
        return $authUser->can('ForceDelete:CuratorMedia');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CuratorMedia');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CuratorMedia');
    }

    public function replicate(AuthUser $authUser, CuratorMedia $curatorMedia): bool
    {
        return $authUser->can('Replicate:CuratorMedia');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CuratorMedia');
    }
}
