<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class ProjectPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any_project');
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->hasPermissionTo('view_project')) {
            return true;
        }

        // Check if user is a team member of the project
        return $project->teamMembers()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_project');
    }

    public function update(User $user, Project $project): bool
    {
        if ($user->hasPermissionTo('update_project')) {
            return true;
        }

        // Check if user is the creator
        return $project->creator_id === $user->id;
    }

    public function delete(User $user, Project $project): bool
    {
        if ($user->hasPermissionTo('delete_project')) {
            return true;
        }

        // Check if user is the creator
        return $project->creator_id === $user->id;
    }

    public function restore(User $user): bool
    {
        return $user->hasPermissionTo('restore_project');
    }

    public function forceDelete(User $user): bool
    {
        return $user->hasPermissionTo('force_delete_project');
    }
}
