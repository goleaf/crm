<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

trait HasCrmAccess
{
    /**
     * Determine if the user can access the CRM workspace for a given tenant.
     */
    public function canAccessCrm(?Model $tenant = null): bool
    {
        if (! method_exists($this, 'hasVerifiedEmail') || ! method_exists($this, 'belongsToTeam')) {
            return false;
        }

        $tenant ??= Filament::getTenant();

        return $tenant !== null
            && $this->hasVerifiedEmail()
            && $this->belongsToTeam($tenant);
    }
}
