<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WorkflowDefinition;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class WorkflowDefinitionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, WorkflowDefinition $workflowDefinition): bool
    {
        return $this->canAccessTenant($user) && $this->workflowInTenant($workflowDefinition);
    }

    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function update(User $user, WorkflowDefinition $workflowDefinition): bool
    {
        return $this->canAccessTenant($user) && $this->workflowInTenant($workflowDefinition);
    }

    public function delete(User $user, WorkflowDefinition $workflowDefinition): bool
    {
        return $this->canAccessTenant($user) && $this->workflowInTenant($workflowDefinition);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function restore(User $user, WorkflowDefinition $workflowDefinition): bool
    {
        return $this->canAccessTenant($user) && $this->workflowInTenant($workflowDefinition);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function forceDelete(User $user, WorkflowDefinition $workflowDefinition): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $this->workflowInTenant($workflowDefinition)
            && $user->hasTeamRole($tenant, 'admin');
    }

    public function forceDeleteAny(User $user): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $user->hasTeamRole($tenant, 'admin');
    }

    private function canAccessTenant(User $user): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $user->hasVerifiedEmail()
            && $user->currentTeam !== null
            && $user->belongsToTeam($tenant);
    }

    private function workflowInTenant(WorkflowDefinition $workflowDefinition): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $workflowDefinition->team?->is($tenant);
    }
}
