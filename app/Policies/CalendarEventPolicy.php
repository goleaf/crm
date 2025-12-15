<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class CalendarEventPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function view(User $user, CalendarEvent $event): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($event);
    }

    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function update(User $user, CalendarEvent $event): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($event);
    }

    public function delete(User $user, CalendarEvent $event): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($event);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function restore(User $user, CalendarEvent $event): bool
    {
        return $this->canAccessTenant($user) && $this->inTenant($event);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    public function forceDelete(User $user, CalendarEvent $event): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && $this->inTenant($event)
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

    private function inTenant(CalendarEvent $event): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $event->team?->is($tenant);
    }
}
