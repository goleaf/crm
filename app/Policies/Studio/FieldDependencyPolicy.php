<?php

declare(strict_types=1);

namespace App\Policies\Studio;

use App\Models\Studio\FieldDependency;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class FieldDependencyPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FieldDependency');
    }

    public function view(AuthUser $authUser, FieldDependency $fieldDependency): bool
    {
        return $authUser->can('View:FieldDependency');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FieldDependency');
    }

    public function update(AuthUser $authUser, FieldDependency $fieldDependency): bool
    {
        return $authUser->can('Update:FieldDependency');
    }

    public function delete(AuthUser $authUser, FieldDependency $fieldDependency): bool
    {
        return $authUser->can('Delete:FieldDependency');
    }

    public function restore(AuthUser $authUser, FieldDependency $fieldDependency): bool
    {
        return $authUser->can('Restore:FieldDependency');
    }

    public function forceDelete(AuthUser $authUser, FieldDependency $fieldDependency): bool
    {
        return $authUser->can('ForceDelete:FieldDependency');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FieldDependency');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FieldDependency');
    }

    public function replicate(AuthUser $authUser, FieldDependency $fieldDependency): bool
    {
        return $authUser->can('Replicate:FieldDependency');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FieldDependency');
    }
}
