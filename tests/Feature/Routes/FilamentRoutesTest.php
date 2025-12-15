<?php

declare(strict_types=1);

use App\Models\User;

use function Spatie\PestPluginRouteTest\routeTesting;

describe('Filament Routes Test', function (): void {
    it('can access Filament admin routes', function (): void {
        $admin = User::factory()->create();
        // Assuming default setup where authenticated user can access panel.
        // Real-world might need 'admin' role or similar.
        // But for now detailed in docs 'can access Filament admin routes' uses factory.

        // We might need to give permission if Shield is active.
        // Based on agents.md Shield IS active: "Filament Shield Integration".
        // So checking if we need to assign a role.
        // "Super admin role (super_admin) bypasses all permission checks".

        $team = \App\Models\Team::factory()->create();
        $admin->teams()->attach($team);

        // We might need to give permission if Shield is active.
        // Based on agents.md Shield IS active: "Filament Shield Integration".
        // So checking if we need to assign a role.
        // "Super admin role (super_admin) bypasses all permission checks".

        $role = Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin->assignRole($role);

        routeTesting()
            ->actingAs($admin)
            ->bind('tenant', $team) // Bind the tenant parameter for tenant-aware routes
            ->only(['filament.app.*'])
            ->except([
                'filament.app.resources.*.create',
                'filament.app.resources.*.edit',
                'filament.app.auth.*', // Skip auth routes if they are guest only or weird
                'filament.app.auth.login', // Guest only
                'filament.app.auth.password-reset.*', // Guest only
                // Exclude logout as it redirects
                'filament.app.auth.logout',
            ])
            ->assertAllRoutesAreAccessible();
    });

    it('redirects guests from Filament admin routes', function (): void {
        routeTesting()
            ->only(['filament.app.pages.dashboard'])
            ->assertAllRoutesRedirect();
    });
});
