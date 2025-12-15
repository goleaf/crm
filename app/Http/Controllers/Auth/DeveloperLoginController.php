<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Developer Login Controller
 *
 * Provides quick authentication for development and testing environments.
 * This controller is only accessible in local/testing environments and should
 * never be available in production.
 *
 * ## Usage
 *
 * Access via signed GET request with email parameter:
 * ```php
 * // Generate signed URL (required since 2025-12-08)
 * $url = URL::signedRoute('dev.login', ['email' => 'user@example.com']);
 *
 * // With expiration (recommended)
 * $url = URL::temporarySignedRoute('dev.login', now()->addMinutes(30), [
 *     'email' => 'user@example.com',
 *     'redirect' => '/dashboard',
 * ]);
 * ```
 *
 * ## Security
 *
 * - Only available when `APP_ENV` is `local` or `testing`
 * - Requires `DEV_LOGIN_ENABLED=true` in the environment
 * - Returns 404 in production environments
 * - Requires signed URLs (403 for unsigned/tampered URLs)
 * - Logs all authentication attempts with IP address
 * - Creates missing users (local/testing only)
 *
 * ## Breaking Changes (2025-12-08)
 *
 * - Route now requires 'signed' middleware
 * - All URLs must be generated with URL::signedRoute() or URL::temporarySignedRoute()
 * - Unsigned URLs return 403 Forbidden
 *
 * ## Multi-Tenancy Support
 *
 * When no explicit redirect URL is provided, the controller automatically
 * redirects to the user's current team dashboard in the Filament panel.
 * This ensures proper tenant context is maintained after login.
 *
 * ## Testing
 *
 * @see tests/Feature/Auth/DeveloperLoginTest.php for comprehensive test coverage
 * @see routes/web.php for route registration
 * @see config/login-link.php for middleware configuration
 * @see docs/deployment/config-login-link-signed-middleware.md for deployment guide
 */
final class DeveloperLoginController extends Controller
{
    /**
     * Handle developer login (local and testing environments only).
     *
     * Authenticates a user by email without requiring a password.
     * This is a convenience feature for local development and automated testing only.
     *
     * ## Request Parameters
     *
     * - `email` (required): User email address to authenticate
     * - `redirect` (optional): URL to redirect after successful login (defaults to tenant dashboard)
     *
     * ## Response Behavior
     *
     * - **Success**: Redirects to specified URL or tenant dashboard with success message
     * - **Missing Email**: Redirects to login page with error message
     * - **User Not Found**: Redirects to login page with error message
     * - **Production Environment**: Returns 404 Not Found
     *
     * ## Translation Keys
     *
     * - `app.messages.developer_login_email_required`
     * - `app.messages.developer_login_user_not_found`
     * - `app.messages.developer_login_success`
     *
     * @param Request $request The HTTP request containing email and optional redirect parameters
     *
     * @return RedirectResponse Redirects to the specified URL or tenant dashboard after login
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException When accessed outside local/testing environment
     */
    public function __invoke(Request $request): RedirectResponse
    {
        // Only allow in local and testing environments
        if (! app()->environment(['local', 'testing']) || ! (bool) env('DEV_LOGIN_ENABLED', false)) {
            abort(404);
        }

        $email = $request->query('email');
        $redirectUrl = $request->query('redirect');
        $name = $request->query('name');

        if (! $email) {
            return to_route('login')
                ->with('error', __('app.messages.developer_login_email_required'));
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return to_route('login')
                ->with('error', __('app.messages.developer_login_email_required'));
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => is_string($name) && $name !== '' ? $name : Str::of($email)->before('@')->toString(),
                'email_verified_at' => now(),
                'password' => Str::random(32),
            ],
        );

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $this->ensureUserHasTeam($user);

        Auth::login($user);

        Log::info('Developer login', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        // Determine redirect URL with tenant support
        $finalRedirectUrl = $this->resolveRedirectUrl($user, $redirectUrl);

        return redirect($finalRedirectUrl)
            ->with('success', __('app.messages.developer_login_success', ['name' => $user->name]));
    }

    private function ensureUserHasTeam(User $user): void
    {
        if ($user->currentTeam !== null) {
            return;
        }

        $firstTeam = $user->allTeams()->first();

        if ($firstTeam !== null) {
            $user->switchTeam($firstTeam);

            return;
        }

        $team = $user->ownedTeams()->create([
            'name' => $user->name . "'s Team",
            'personal_team' => true,
        ]);

        $user->switchTeam($team);
    }

    /**
     * Resolve the redirect URL with tenant support.
     *
     * If no explicit redirect URL is provided, attempts to redirect to the
     * user's current team dashboard in the Filament panel. Falls back to
     * the root URL if no team is available.
     */
    private function resolveRedirectUrl(User $user, ?string $redirectUrl): string
    {
        // If explicit redirect URL provided, use it
        if ($redirectUrl !== null && $redirectUrl !== '') {
            return $redirectUrl;
        }

        // Try to get the user's current team for tenant-aware redirect
        $team = $user->currentTeam;

        if ($team !== null) {
            try {
                // Get the Filament panel and generate tenant-aware dashboard URL
                $panel = Filament::getPanel('app');

                return $panel->getUrl($team);
            } catch (\Throwable) {
                // Fall through to default redirect
            }
        }

        // Fallback to root URL
        return '/';
    }
}
