<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BulkOperation;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class BulkOperationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BulkOperation');
    }

    public function view(AuthUser $authUser, BulkOperation $bulkOperation): bool
    {
        return $authUser->can('View:BulkOperation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BulkOperation');
    }

    public function update(AuthUser $authUser, BulkOperation $bulkOperation): bool
    {
        return $authUser->can('Update:BulkOperation');
    }

    public function delete(AuthUser $authUser, BulkOperation $bulkOperation): bool
    {
        return $authUser->can('Delete:BulkOperation');
    }

    public function restore(AuthUser $authUser, BulkOperation $bulkOperation): bool
    {
        return $authUser->can('Restore:BulkOperation');
    }

    public function forceDelete(AuthUser $authUser, BulkOperation $bulkOperation): bool
    {
        return $authUser->can('ForceDelete:BulkOperation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BulkOperation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BulkOperation');
    }

    public function replicate(AuthUser $authUser, BulkOperation $bulkOperation): bool
    {
        return $authUser->can('Replicate:BulkOperation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BulkOperation');
    }
}
