<?php

declare(strict_types=1);

use function Spatie\PestPluginRouteTest\routeTesting;

describe('Public Routes', function (): void {
    it('can access home page', function (): void {
        routeTesting()
            ->only(['home'])
            ->assertAllRoutesAreAccessible();
    });

    it('can access terms of service', function (): void {
        routeTesting()
            ->only(['terms.show'])
            ->assertAllRoutesAreAccessible();
    });

    it('can access privacy policy', function (): void {
        routeTesting()
            ->only(['policy.show'])
            ->assertAllRoutesAreAccessible();
    });

    it('can access security.txt', function (): void {
        routeTesting()
            ->only(['security.txt'])
            ->assertAllRoutesAreAccessible();
    });

    it('can access all public routes', function (): void {
        routeTesting()
            ->only([
                'home',
                'terms.show',
                'policy.show',
                'security.txt',
            ])
            ->assertAllRoutesAreAccessible();
    });

    it('redirects to external discord invite', function (): void {
        routeTesting()
            ->only(['discord'])
            ->assertAllRoutesRedirect();
    });
});
