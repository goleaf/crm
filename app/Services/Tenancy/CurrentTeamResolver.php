<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Models\Team;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Throwable;

final class CurrentTeamResolver
{
    public static function resolve(?Authenticatable $user = null): ?Team
    {
        try {
            $tenant = Filament::getTenant();

            if ($tenant instanceof Team) {
                return $tenant;
            }
        } catch (Throwable) {
            // Filament may not be initialized (e.g. CLI, tests)
        }

        $user ??= Auth::guard('web')->user();
        $user ??= Auth::user();

        if ($user instanceof Authenticatable && method_exists($user, 'currentTeam')) {
            /** @var Team|null $team */
            $team = $user->currentTeam;

            if ($team instanceof Team) {
                return $team;
            }

            if (method_exists($user, 'personalTeam')) {
                /** @var Team|null $personal */
                $personal = $user->personalTeam();

                if ($personal instanceof Team) {
                    return $personal;
                }
            }
        }

        return null;
    }

    public static function resolveId(?Authenticatable $user = null): ?int
    {
        return self::resolve($user)?->getKey();
    }
}
