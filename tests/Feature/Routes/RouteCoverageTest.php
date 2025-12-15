<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tests\Feature\Routes\RouteTestingConfig;

describe('Route Coverage', function (): void {
    it('has tests for all public routes', function (): void {
        $publicRoutes = RouteTestingConfig::publicRoutes();
        $registeredRoutes = collect(Route::getRoutes())
            ->filter(fn ($route): bool => $route->getName() !== null)
            ->pluck('name')
            ->toArray();

        foreach ($publicRoutes as $routeName) {
            expect($registeredRoutes)
                ->toContain($routeName, "Route '{$routeName}' is not registered");
        }
    });

    it('has tests for all authenticated routes', function (): void {
        $authenticatedRoutes = RouteTestingConfig::authenticatedRoutes();
        $registeredRoutes = collect(Route::getRoutes())
            ->filter(fn ($route): bool => $route->getName() !== null)
            ->pluck('name')
            ->toArray();

        foreach ($authenticatedRoutes as $routeName) {
            expect($registeredRoutes)
                ->toContain($routeName, "Route '{$routeName}' is not registered");
        }
    });

    it('has tests for all API routes', function (): void {
        $apiRoutes = RouteTestingConfig::apiRoutes();
        $registeredRoutes = collect(Route::getRoutes())
            ->filter(fn ($route): bool => $route->getName() !== null)
            ->pluck('name')
            ->toArray();

        foreach ($apiRoutes as $routeName) {
            expect($registeredRoutes)
                ->toContain($routeName, "Route '{$routeName}' is not registered");
        }
    });

    it('excludes appropriate routes from testing', function (): void {
        $excludedPatterns = RouteTestingConfig::excludedRoutes();

        expect($excludedPatterns)
            ->toContain('telescope.*')
            ->toContain('horizon.*')
            ->toContain('*.store')
            ->toContain('*.update')
            ->toContain('*.destroy');
    });

    it('identifies routes requiring parameters', function (): void {
        $parametricRoutes = RouteTestingConfig::parametricRoutes();

        expect($parametricRoutes)
            ->toHaveKey('notes.print')
            ->toHaveKey('contacts.show')
            ->toHaveKey('auth.socialite.redirect');

        expect($parametricRoutes['notes.print'])->toContain('note');
        expect($parametricRoutes['contacts.show'])->toContain('contact');
    });

    it('identifies routes that should redirect', function (): void {
        $redirectRoutes = RouteTestingConfig::redirectRoutes();

        expect($redirectRoutes)
            ->toContain('dashboard')
            ->toContain('login')
            ->toContain('discord');
    });

    it('identifies routes requiring signed URLs', function (): void {
        $signedRoutes = RouteTestingConfig::signedRoutes();

        expect($signedRoutes)
            ->toContain('verification.verify')
            ->toContain('team-invitations.accept');
    });

    it('identifies routes supporting Precognition', function (): void {
        $precognitionRoutes = RouteTestingConfig::precognitionRoutes();

        expect($precognitionRoutes)
            ->toContain('contacts.store')
            ->toContain('contacts.update');
    });

    it('can determine if route should be excluded', function (): void {
        expect(RouteTestingConfig::shouldExclude('telescope.index'))->toBeTrue();
        expect(RouteTestingConfig::shouldExclude('horizon.index'))->toBeTrue();
        expect(RouteTestingConfig::shouldExclude('contacts.store'))->toBeTrue();
        expect(RouteTestingConfig::shouldExclude('home'))->toBeFalse();
    });

    it('can determine if route requires authentication', function (): void {
        expect(RouteTestingConfig::requiresAuth('dashboard'))->toBeTrue();
        expect(RouteTestingConfig::requiresAuth('calendar'))->toBeTrue();
        expect(RouteTestingConfig::requiresAuth('contacts.index'))->toBeTrue();
        expect(RouteTestingConfig::requiresAuth('home'))->toBeFalse();
    });

    it('can determine if route is guest-only', function (): void {
        expect(RouteTestingConfig::isGuestOnly('login'))->toBeTrue();
        expect(RouteTestingConfig::isGuestOnly('register'))->toBeTrue();
        expect(RouteTestingConfig::isGuestOnly('dashboard'))->toBeFalse();
    });

    it('lists all testable routes', function (): void {
        $testableRoutes = RouteTestingConfig::testableRoutes();

        expect($testableRoutes)
            ->toBeArray()
            ->not->toBeEmpty();

        // Should include public routes
        expect($testableRoutes)->toContain('home');
        expect($testableRoutes)->toContain('terms.show');

        // Should include authenticated routes
        expect($testableRoutes)->toContain('dashboard');
        expect($testableRoutes)->toContain('filament.app.pages.dashboard');
        expect($testableRoutes)->toContain('calendar');

        // Should include API routes
        expect($testableRoutes)->toContain('contacts.index');
    });
});
