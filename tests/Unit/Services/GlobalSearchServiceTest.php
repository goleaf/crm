<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\SupportCase;
use App\Models\Task;
use App\Models\User;
use App\Services\Search\GlobalSearchService;
use Illuminate\Support\Arr;

beforeEach(function (): void {
    $this->service = resolve(GlobalSearchService::class);
});

it('returns team-scoped results across entities', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($user);

    $company = Company::factory()->for($user->currentTeam)->create(['name' => 'Acme Rocket Co']);
    $person = \App\Models\People::factory()->for($user->currentTeam)->create(['name' => 'Jane Doe']);
    $opp = Opportunity::factory()->for($user->currentTeam)->create(['name' => 'Rocket Deal']);
    $task = Task::factory()->for($user->currentTeam)->create(['title' => 'Follow up with Jane']);
    $case = SupportCase::factory()->for($user->currentTeam)->create(['subject' => 'Rocket issue']);

    // Another team to ensure isolation
    $otherTeamUser = User::factory()->withPersonalTeam()->create();
    Company::factory()->for($otherTeamUser->currentTeam)->create(['name' => 'Other Co']);
    \App\Models\People::factory()->for($otherTeamUser->currentTeam)->create(['name' => 'Other Person']);
    Opportunity::factory()->for($otherTeamUser->currentTeam)->create(['name' => 'Other Deal']);
    Task::factory()->for($otherTeamUser->currentTeam)->create(['title' => 'Other Task']);
    SupportCase::factory()->for($otherTeamUser->currentTeam)->create(['subject' => 'Other Case']);

    $results = $this->service->search('Rocket');

    expect(Arr::flatten($results))->toContain($company, $opp, $case);

    expect($results['companies']->contains('name', 'Other Co'))->toBeFalse();
    expect($results['tasks'])->toHaveCount(1);
    expect($results['people'])->toHaveCount(1);
});

it('applies advanced filters when provided', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($user);

    $matching = Company::factory()->for($user->currentTeam)->create(['name' => 'Beta Corp', 'industry' => 'software']);
    Company::factory()->for($user->currentTeam)->create(['name' => 'Gamma Corp', 'industry' => 'finance']);

    $results = $this->service->search(
        'Corp',
        filters: [
            'companies' => [
                ['field' => 'industry', 'operator' => '=', 'value' => 'software'],
            ],
        ],
    );

    expect($results['companies'])
        ->toHaveCount(1)
        ->first()->is($matching)->toBeTrue();
});
