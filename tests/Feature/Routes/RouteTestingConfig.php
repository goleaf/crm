<?php

declare(strict_types=1);

namespace Tests\Feature\Routes;

/**
 * Route Testing Configuration
 *
 * This class provides centralized configuration for route testing,
 * including route groups, exclusions, and common test patterns.
 */
final class RouteTestingConfig
{
    /**
     * Routes that should be excluded from automated testing
     */
    public static function excludedRoutes(): array
    {
        return [
            // Third-party package routes
            'telescope.*',
            'horizon.*',
            'clockwork.*',
            'debugbar.*',

            // Development-only routes
            '_ignition.*',
            'dev.login', // Developer login (tested separately in DeveloperLoginTest)
            'filament.app.filament.app.dev-login-form', // Developer login form (tested separately)

            // Form submission routes (tested separately)
            '*.store',
            '*.update',
            '*.destroy',

            'filament.*.create', // Complex Filament forms
            'filament.*.edit',
            'filament.app.auth.logout',
            'filament.app.auth.login',
            'filament.app.auth.password-reset.*',
            'livewire.update', // Livewire route
            'livewire.upload-file',
            'livewire.preview-file',

            // Routes that require specific signed URLs
            'verification.verify',
            'team-invitations.accept',

            // Social auth callback (requires external provider)
            'auth.socialite.callback',
        ];
    }

    /**
     * Public routes accessible without authentication
     */
    public static function publicRoutes(): array
    {
        return [
            'home',
            'terms.show',
            'policy.show',
            'security.txt',
            'discord',
        ];
    }

    /**
     * Routes that require authentication
     */
    public static function authenticatedRoutes(): array
    {
        return [
            'dashboard',
            'filament.app.pages.dashboard',
            'calendar',
            'calendar.export.ical',
            'notes.print',
            'purchase-orders.index',
            'laravelschemadocs.index',
            'laravelschemadocs.erd',
            'laravelschemadocs.show',
        ];
    }

    /**
     * API routes that require Sanctum authentication
     */
    public static function apiRoutes(): array
    {
        return [
            'contacts.index',
            'contacts.show',
        ];
    }

    /**
     * Guest-only routes (redirect when authenticated)
     */
    public static function guestRoutes(): array
    {
        return [
            'login',
            'register',
            'password.request',
        ];
    }

    /**
     * Routes that require specific parameters
     */
    public static function parametricRoutes(): array
    {
        return [
            'notes.print' => ['note'],
            'contacts.show' => ['contact'],
            'auth.socialite.redirect' => ['provider'],
            'laravelschemadocs.show' => ['name'],
        ];
    }

    /**
     * Routes that should redirect
     */
    public static function redirectRoutes(): array
    {
        return [
            'dashboard', // Redirects to app URL
            'login', // Redirects to app URL
            'register', // Redirects to app URL
            'password.request', // Redirects to app URL
            'discord', // Redirects to external URL
        ];
    }

    /**
     * Routes that require signed URLs
     */
    public static function signedRoutes(): array
    {
        return [
            'verification.verify',
            'team-invitations.accept',
        ];
    }

    /**
     * Routes that support Precognition validation
     */
    public static function precognitionRoutes(): array
    {
        return [
            'contacts.store',
            'contacts.update',
        ];
    }

    /**
     * Get all testable routes (excluding complex ones)
     */
    public static function testableRoutes(): array
    {
        return array_merge(
            self::publicRoutes(),
            self::authenticatedRoutes(),
            self::apiRoutes(),
        );
    }

    /**
     * Check if a route should be excluded from testing
     */
    public static function shouldExclude(string $routeName): bool
    {
        foreach (self::excludedRoutes() as $pattern) {
            if (str_contains((string) $pattern, '*')) {
                $regex = '/^' . str_replace('*', '.*', $pattern) . '$/';
                if (preg_match($regex, $routeName)) {
                    return true;
                }
            } elseif ($pattern === $routeName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get required parameters for a route
     */
    public static function getRequiredParameters(string $routeName): array
    {
        return self::parametricRoutes()[$routeName] ?? [];
    }

    /**
     * Check if route requires authentication
     */
    public static function requiresAuth(string $routeName): bool
    {
        return in_array($routeName, self::authenticatedRoutes(), true)
            || in_array($routeName, self::apiRoutes(), true);
    }

    /**
     * Check if route is guest-only
     */
    public static function isGuestOnly(string $routeName): bool
    {
        return in_array($routeName, self::guestRoutes(), true);
    }

    /**
     * Check if route should redirect
     */
    public static function shouldRedirect(string $routeName): bool
    {
        return in_array($routeName, self::redirectRoutes(), true);
    }

    /**
     * Check if route requires signed URL
     */
    public static function requiresSignedUrl(string $routeName): bool
    {
        return in_array($routeName, self::signedRoutes(), true);
    }

    /**
     * Check if route supports Precognition
     */
    public static function supportsPrecognition(string $routeName): bool
    {
        return in_array($routeName, self::precognitionRoutes(), true);
    }
}
