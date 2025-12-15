<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCrmAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = config('crm.auth_guard', 'web');

        if (! Auth::guard($guard)->check()) {
            return $this->unauthenticatedResponse($request);
        }

        $user = Auth::guard($guard)->user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return $this->forbiddenResponse($request, 'Email verification required.');
        }

        if (! $user?->currentTeam) {
            return $this->forbiddenResponse($request, 'Team context required.');
        }

        return $next($request);
    }

    private function unauthenticatedResponse(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest('/login');
    }

    private function forbiddenResponse(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()->guest('/login')->with('error', $message);
    }
}
