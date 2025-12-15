<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ImportJob;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class ImportJobPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ImportJob');
    }

    public function view(AuthUser $authUser, ImportJob $importJob): bool
    {
        return $authUser->can('View:ImportJob');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ImportJob');
    }

    public function update(AuthUser $authUser, ImportJob $importJob): bool
    {
        return $authUser->can('Update:ImportJob');
    }

    public function delete(AuthUser $authUser, ImportJob $importJob): bool
    {
        return $authUser->can('Delete:ImportJob');
    }

    public function restore(AuthUser $authUser, ImportJob $importJob): bool
    {
        return $authUser->can('Restore:ImportJob');
    }

    public function forceDelete(AuthUser $authUser, ImportJob $importJob): bool
    {
        return $authUser->can('ForceDelete:ImportJob');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ImportJob');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ImportJob');
    }

    public function replicate(AuthUser $authUser, ImportJob $importJob): bool
    {
        return $authUser->can('Replicate:ImportJob');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ImportJob');
    }
}
