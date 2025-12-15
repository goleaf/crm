<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $guard = config('crm.auth_guard', 'web');
        $user = Auth::guard($guard)->user();

        if ($user === null) {
            return $this->deny($request, 'Unauthenticated.', 401);
        }

        $permissions = Collection::make($permissions)
            ->flatMap(fn (string $permission): array => array_filter(explode('|', $permission)))
            ->filter()
            ->values();

        if ($permissions->isEmpty() || $user->hasAnyPermission($permissions->all())) {
            return $next($request);
        }

        return $this->deny($request, 'Forbidden.', 403);
    }

    private function deny(Request $request, string $message, int $status): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return abort($status, $message);
    }
}
