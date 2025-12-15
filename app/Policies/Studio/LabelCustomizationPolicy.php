<?php

declare(strict_types=1);

namespace App\Policies\Studio;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Studio\LabelCustomization;
use Illuminate\Auth\Access\HandlesAuthorization;

class LabelCustomizationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LabelCustomization');
    }

    public function view(AuthUser $authUser, LabelCustomization $labelCustomization): bool
    {
        return $authUser->can('View:LabelCustomization');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LabelCustomization');
    }

    public function update(AuthUser $authUser, LabelCustomization $labelCustomization): bool
    {
        return $authUser->can('Update:LabelCustomization');
    }

    public function delete(AuthUser $authUser, LabelCustomization $labelCustomization): bool
    {
        return $authUser->can('Delete:LabelCustomization');
    }

    public function restore(AuthUser $authUser, LabelCustomization $labelCustomization): bool
    {
        return $authUser->can('Restore:LabelCustomization');
    }

    public function forceDelete(AuthUser $authUser, LabelCustomization $labelCustomization): bool
    {
        return $authUser->can('ForceDelete:LabelCustomization');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LabelCustomization');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LabelCustomization');
    }

    public function replicate(AuthUser $authUser, LabelCustomization $labelCustomization): bool
    {
        return $authUser->can('Replicate:LabelCustomization');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LabelCustomization');
    }

}