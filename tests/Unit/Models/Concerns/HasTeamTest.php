<?php

declare(strict_types=1);

use App\Models\Opportunity;
use App\Models\Team;
use App\Models\User;

test('team id defaults to the active team when missing', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);

    $opportunity = Opportunity::factory()
        ->state(['team_id' => null])
        ->create();

    expect($opportunity->team_id)->toBe($user->currentTeam->getKey());
});

test('team scoped models are isolated by the active tenant', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $otherTeam = Team::factory()->state([
        'personal_team' => false,
        'user_id' => $user->getKey(),
    ])->create();
    $otherTeam->users()->attach($user);

    $this->actingAs($user);

    $primaryTeam = $user->currentTeam;

    $primaryOpportunity = Opportunity::factory()->for($primaryTeam)->create(['name' => 'Primary']);

    $user->switchTeam($otherTeam);

    $secondaryOpportunity = Opportunity::factory()->create(['name' => 'Secondary']);

    $user->switchTeam($primaryTeam);

    expect(Opportunity::pluck('id'))
        ->toContain($primaryOpportunity->getKey())
        ->not->toContain($secondaryOpportunity->getKey());

    $user->switchTeam($otherTeam);

    expect(Opportunity::pluck('id'))
        ->toContain($secondaryOpportunity->getKey())
        ->not->toContain($primaryOpportunity->getKey());
});
