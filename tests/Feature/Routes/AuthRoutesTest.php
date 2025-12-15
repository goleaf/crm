<?php

declare(strict_types=1);

use App\Models\User;

use function Spatie\PestPluginRouteTest\routeTesting;

describe('Authentication Routes', function (): void {
    it('can access guest authentication routes', function (): void {
        routeTesting()
            ->only([
                'login',
                'register',
                'password.request',
            ])
            ->assertAllRoutesRedirect(); // These redirect to app URL
    });

    it('can access social auth redirect routes', function (): void {
        routeTesting()
            ->only(['auth.socialite.redirect'])
            ->bind('provider', 'github')
            ->assertAllRoutesRedirect();
    });

    it('redirects authenticated users from guest routes', function (): void {
        $user = User::factory()->create();

        routeTesting()
            ->actingAs($user)
            ->only([
                'login',
                'register',
                'password.request',
            ])
            ->assertAllRoutesRedirect();
    });

    it('can access dashboard when authenticated', function (): void {
        $user = User::factory()->create();

        routeTesting()
            ->actingAs($user)
            ->only(['dashboard'])
            ->assertAllRoutesRedirect(); // Redirects to app URL
    });
});
