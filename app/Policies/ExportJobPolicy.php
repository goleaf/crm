<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ExportJob;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExportJobPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ExportJob');
    }

    public function view(AuthUser $authUser, ExportJob $exportJob): bool
    {
        return $authUser->can('View:ExportJob');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ExportJob');
    }

    public function update(AuthUser $authUser, ExportJob $exportJob): bool
    {
        return $authUser->can('Update:ExportJob');
    }

    public function delete(AuthUser $authUser, ExportJob $exportJob): bool
    {
        return $authUser->can('Delete:ExportJob');
    }

    public function restore(AuthUser $authUser, ExportJob $exportJob): bool
    {
        return $authUser->can('Restore:ExportJob');
    }

    public function forceDelete(AuthUser $authUser, ExportJob $exportJob): bool
    {
        return $authUser->can('ForceDelete:ExportJob');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ExportJob');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ExportJob');
    }

    public function replicate(AuthUser $authUser, ExportJob $exportJob): bool
    {
        return $authUser->can('Replicate:ExportJob');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ExportJob');
    }

}