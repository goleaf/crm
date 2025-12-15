<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Tenancy\CurrentTeamResolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final readonly class ApplyTenantScopes
{
    public function handle(Request $request, Closure $next): mixed
    {
        $tenant = CurrentTeamResolver::resolve();

        if (! $tenant instanceof \App\Models\Team) {
            return $next($request);
        }

        setPermissionsTeamId($tenant->getKey());

        User::addGlobalScope(
            filament()->getTenancyScopeName(),
            fn (Builder $query) => $query
                ->whereHas('teams', fn (Builder $query) => $query->where('teams.id', $tenant->getKey()))
                ->orWhereHas('ownedTeams', fn (Builder $query) => $query->where('teams.id', $tenant->getKey())),
        );

        return $next($request);
    }
}
