<?php

declare(strict_types=1);

use App\Models\User;

it('renders calendar page and can schedule an event', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $user->switchTeam($user->ownedTeams()->first());

    $this->actingAs($user)
        ->get('/calendar')
        ->assertOk();

    $payload = [
        'title' => 'Test Meeting',
        'type' => 'meeting',
        'start_at' => now()->addDay()->format('Y-m-d\TH:i'),
        'end_at' => now()->addDay()->addHour()->format('Y-m-d\TH:i'),
        'location' => 'Zoom',
    ];

    $this->actingAs($user)
        ->post('/calendar', $payload)
        ->assertRedirect(route('calendar'));

    $this->assertDatabaseHas('calendar_events', [
        'title' => 'Test Meeting',
        'team_id' => $user->currentTeam?->getKey(),
    ]);
});
