<?php

declare(strict_types=1);

use App\Models\Note;
use App\Models\User;

use function Spatie\PestPluginRouteTest\routeTesting;

describe('Authenticated Routes', function (): void {
    it('requires authentication for protected routes', function (): void {
        $this->get(route('calendar'))
            ->assertRedirect(route('login'));
    });

    it('can access calendar when authenticated', function (): void {
        $user = User::factory()->create();

        routeTesting()
            ->actingAs($user)
            ->only(['calendar'])
            ->assertAllRoutesAreAccessible();
    });

    it('can access note print route when authenticated', function (): void {
        $user = User::factory()->create();
        $note = Note::factory()->create([
            'team_id' => $user->currentTeam->id,
        ]);

        routeTesting()
            ->actingAs($user)
            ->bind('note', $note)
            ->only(['notes.print'])
            ->assertAllRoutesAreAccessible();
    });

    it('can access purchase orders when authenticated', function (): void {
        $user = User::factory()->create();

        routeTesting()
            ->actingAs($user)
            ->only(['purchase-orders.index'])
            ->assertAllRoutesAreAccessible();
    });

    it('can access team invitation acceptance', function (): void {
        $user = User::factory()->create();
        $team = \App\Models\Team::factory()->create();
        $invitation = $team->teamInvitations()->create([
            'email' => $user->email,
            'role' => 'member',
        ]);

        // Generate signed URL
        $url = \Illuminate\Support\Facades\URL::signedRoute(
            'team-invitations.accept',
            ['invitation' => $invitation]
        );

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect();
    });

    it('can access email verification route', function (): void {
        $user = User::factory()->unverified()->create();

        // Generate signed URL
        $url = \Illuminate\Support\Facades\URL::signedRoute(
            'verification.verify',
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect(route('dashboard'));
    });
});
