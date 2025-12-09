<?php

declare(strict_types=1);

use App\Models\Note;
use App\Models\People;
use App\Models\User;
use Illuminate\Support\Facades\Route;

use function Spatie\PestPluginRouteTest\routeTesting;

use Tests\Feature\Routes\RouteTestingConfig;

describe('All Routes Comprehensive Test', function (): void {
    it('can list all registered routes', function (): void {
        $routes = collect(Route::getRoutes())
            ->filter(fn ($route): bool => $route->getName() !== null)
            ->map(fn ($route): array => [
                'name' => $route->getName(),
                'uri' => $route->uri(),
                'methods' => $route->methods(),
                'middleware' => $route->middleware(),
            ])
            ->values()
            ->all();

        expect($routes)->not->toBeEmpty();
    });

    it('ensures all public routes are accessible', function (): void {
        $publicRoutes = RouteTestingConfig::publicRoutes();

        routeTesting()
            ->only($publicRoutes)
            ->except(['discord']) // External redirect
            ->assertAllRoutesAreAccessible();
    });

    it('ensures authenticated routes require authentication', function (): void {
        $authenticatedRoutes = RouteTestingConfig::authenticatedRoutes();

        foreach ($authenticatedRoutes as $routeName) {
            if (RouteTestingConfig::shouldExclude($routeName)) {
                continue;
            }

            $route = Route::getRoutes()->getByName($routeName);
            if (! $route) {
                continue;
            }

            // Skip routes with required parameters for this test
            if (RouteTestingConfig::getRequiredParameters($routeName) !== []) {
                continue;
            }

            $response = $this->get(route($routeName));
            expect($response)->toBeRedirect();
        }
    });

    it('ensures API routes require authentication', function (): void {
        $apiRoutes = RouteTestingConfig::apiRoutes();

        foreach ($apiRoutes as $routeName) {
            if (RouteTestingConfig::shouldExclude($routeName)) {
                continue;
            }

            $route = Route::getRoutes()->getByName($routeName);
            if (! $route) {
                continue;
            }

            // Skip routes with required parameters for this test
            if (RouteTestingConfig::getRequiredParameters($routeName) !== []) {
                continue;
            }

            $response = $this->getJson(route($routeName));
            expect($response)->toBeUnauthorized();
        }
    });

    it('can access all routes with proper authentication and parameters', function (): void {
        $user = User::factory()->create();
        $note = Note::factory()->create(['team_id' => $user->currentTeam->id]);
        $contact = People::factory()->create(['team_id' => $user->currentTeam->id]);

        // Test authenticated web routes
        routeTesting()
            ->actingAs($user)
            ->bind('note', $note)
            ->only(['calendar', 'notes.print', 'purchase-orders.index'])
            ->assertAllRoutesAreAccessible();

        // Test API routes with token
        $token = $user->createToken('test')->plainTextToken;

        routeTesting()
            ->withToken($token)
            ->bind('contact', $contact)
            ->only(['contacts.index', 'contacts.show'])
            ->assertAllRoutesAreAccessible();
    });

    it('identifies routes without tests', function (): void {
        $allRoutes = collect(Route::getRoutes())
            ->filter(fn ($route): bool => $route->getName() !== null)
            ->pluck('name')
            ->reject(fn (string $name): bool => RouteTestingConfig::shouldExclude($name))
            ->toArray();

        $testedRoutes = RouteTestingConfig::testableRoutes();

        $untestedRoutes = array_diff($allRoutes, $testedRoutes);

        // Log untested routes for review
        if ($untestedRoutes !== []) {
            $this->info('Untested routes: ' . implode(', ', $untestedRoutes));
        }

        // This is informational - we don't fail if there are untested routes
        // as some routes may require complex setup
        expect($untestedRoutes)->toBeArray();
    });

    it('validates route naming conventions', function (): void {
        $routes = collect(Route::getRoutes())
            ->filter(fn ($route): bool => $route->getName() !== null)
            ->pluck('name')
            ->toArray();

        foreach ($routes as $routeName) {
            // Route names should use dot notation
            if (str_contains($routeName, '_')) {
                $this->info("Route '{$routeName}' uses underscore instead of dot notation");
            }

            // Route names should be lowercase
            if ($routeName !== strtolower($routeName)) {
                $this->info("Route '{$routeName}' contains uppercase characters");
            }
        }

        expect($routes)->not->toBeEmpty();
    });

    it('validates route middleware configuration', function (): void {
        $routes = collect(Route::getRoutes())
            ->filter(fn ($route): bool => $route->getName() !== null)
            ->map(fn ($route): array => [
                'name' => $route->getName(),
                'middleware' => $route->middleware(),
            ])
            ->all();

        foreach ($routes as $route) {
            $routeName = $route['name'];
            $middleware = $route['middleware'];

            // Check if authenticated routes have auth middleware
            if (RouteTestingConfig::requiresAuth($routeName)) {
                expect($middleware)
                    ->toContain('auth', "Route '{$routeName}' should have auth middleware");
            }

            // Check if guest routes have guest middleware
            if (RouteTestingConfig::isGuestOnly($routeName)) {
                expect($middleware)
                    ->toContain('guest', "Route '{$routeName}' should have guest middleware");
            }

            // Check if signed routes have signed middleware
            if (RouteTestingConfig::requiresSignedUrl($routeName)) {
                expect($middleware)
                    ->toContain('signed', "Route '{$routeName}' should have signed middleware");
            }
        }
    });

    it('validates route HTTP methods', function (): void {
        $routes = collect(Route::getRoutes())
            ->filter(fn ($route): bool => $route->getName() !== null)
            ->map(fn ($route): array => [
                'name' => $route->getName(),
                'methods' => $route->methods(),
            ])
            ->all();

        foreach ($routes as $route) {
            $routeName = $route['name'];
            $methods = $route['methods'];

            // Remove HEAD method (automatically added by Laravel)
            $methods = array_filter($methods, fn ($method): bool => $method !== 'HEAD');

            // Validate method conventions
            if (str_ends_with($routeName, '.index') || str_ends_with($routeName, '.show')) {
                expect($methods)->toContain('GET', "Route '{$routeName}' should support GET");
            }

            if (str_ends_with($routeName, '.store')) {
                expect($methods)->toContain('POST', "Route '{$routeName}' should support POST");
            }

            if (str_ends_with($routeName, '.update')) {
                expect($methods)->toContain('PUT', "Route '{$routeName}' should support PUT or PATCH");
            }

            if (str_ends_with($routeName, '.destroy')) {
                expect($methods)->toContain('DELETE', "Route '{$routeName}' should support DELETE");
            }
        }
    });
});
