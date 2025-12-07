<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Permissions\TeamResolver as PermissionTeamResolver;
use App\Services\Tenancy\CurrentTeamResolver;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTeamContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $team = CurrentTeamResolver::resolve();

        if (! $team instanceof \App\Models\Team) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Team context required.'], 403)
                : abort(403, 'Team context required.');
        }

        app(PermissionTeamResolver::class)->setPermissionsTeamId($team);

        try {
            Filament::setTenant($team);
        } catch (\Throwable) {
            // Silently ignore when Filament tenancy is not in use for the request.
        }

        return $next($request);
    }
}
