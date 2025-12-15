<?php

declare(strict_types=1);

namespace App\Policies\Studio;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Studio\LayoutDefinition;
use Illuminate\Auth\Access\HandlesAuthorization;

class LayoutDefinitionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LayoutDefinition');
    }

    public function view(AuthUser $authUser, LayoutDefinition $layoutDefinition): bool
    {
        return $authUser->can('View:LayoutDefinition');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LayoutDefinition');
    }

    public function update(AuthUser $authUser, LayoutDefinition $layoutDefinition): bool
    {
        return $authUser->can('Update:LayoutDefinition');
    }

    public function delete(AuthUser $authUser, LayoutDefinition $layoutDefinition): bool
    {
        return $authUser->can('Delete:LayoutDefinition');
    }

    public function restore(AuthUser $authUser, LayoutDefinition $layoutDefinition): bool
    {
        return $authUser->can('Restore:LayoutDefinition');
    }

    public function forceDelete(AuthUser $authUser, LayoutDefinition $layoutDefinition): bool
    {
        return $authUser->can('ForceDelete:LayoutDefinition');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LayoutDefinition');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LayoutDefinition');
    }

    public function replicate(AuthUser $authUser, LayoutDefinition $layoutDefinition): bool
    {
        return $authUser->can('Replicate:LayoutDefinition');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LayoutDefinition');
    }

}