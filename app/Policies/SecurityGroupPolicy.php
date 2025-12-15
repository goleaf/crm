<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SecurityGroup;
use Illuminate\Auth\Access\HandlesAuthorization;

class SecurityGroupPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SecurityGroup');
    }

    public function view(AuthUser $authUser, SecurityGroup $securityGroup): bool
    {
        return $authUser->can('View:SecurityGroup');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SecurityGroup');
    }

    public function update(AuthUser $authUser, SecurityGroup $securityGroup): bool
    {
        return $authUser->can('Update:SecurityGroup');
    }

    public function delete(AuthUser $authUser, SecurityGroup $securityGroup): bool
    {
        return $authUser->can('Delete:SecurityGroup');
    }

    public function restore(AuthUser $authUser, SecurityGroup $securityGroup): bool
    {
        return $authUser->can('Restore:SecurityGroup');
    }

    public function forceDelete(AuthUser $authUser, SecurityGroup $securityGroup): bool
    {
        return $authUser->can('ForceDelete:SecurityGroup');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SecurityGroup');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SecurityGroup');
    }

    public function replicate(AuthUser $authUser, SecurityGroup $securityGroup): bool
    {
        return $authUser->can('Replicate:SecurityGroup');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SecurityGroup');
    }

}