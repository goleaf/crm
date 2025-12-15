<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DataIntegrityCheck;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class DataIntegrityCheckPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DataIntegrityCheck');
    }

    public function view(AuthUser $authUser, DataIntegrityCheck $dataIntegrityCheck): bool
    {
        return $authUser->can('View:DataIntegrityCheck');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DataIntegrityCheck');
    }

    public function update(AuthUser $authUser, DataIntegrityCheck $dataIntegrityCheck): bool
    {
        return $authUser->can('Update:DataIntegrityCheck');
    }

    public function delete(AuthUser $authUser, DataIntegrityCheck $dataIntegrityCheck): bool
    {
        return $authUser->can('Delete:DataIntegrityCheck');
    }

    public function restore(AuthUser $authUser, DataIntegrityCheck $dataIntegrityCheck): bool
    {
        return $authUser->can('Restore:DataIntegrityCheck');
    }

    public function forceDelete(AuthUser $authUser, DataIntegrityCheck $dataIntegrityCheck): bool
    {
        return $authUser->can('ForceDelete:DataIntegrityCheck');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DataIntegrityCheck');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DataIntegrityCheck');
    }

    public function replicate(AuthUser $authUser, DataIntegrityCheck $dataIntegrityCheck): bool
    {
        return $authUser->can('Replicate:DataIntegrityCheck');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DataIntegrityCheck');
    }
}
