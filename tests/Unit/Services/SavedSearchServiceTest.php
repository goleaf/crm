<?php

declare(strict_types=1);

use App\Models\SavedSearch;
use App\Models\User;
use App\Services\Search\SavedSearchService;

it('saves and lists searches per team', function (): void {
    $service = resolve(SavedSearchService::class);
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);

    $saved = $service->save(
        $user,
        name: 'My Accounts',
        resource: 'companies',
        query: 'Acme',
        filters: [
            ['field' => 'industry', 'operator' => 'contains', 'value' => 'software'],
        ],
    );

    expect($saved)->toBeInstanceOf(SavedSearch::class);
    expect($saved->team_id)->toEqual($user->currentTeam->getKey());

    $listed = $service->list($user, 'companies');

    expect($listed)->toHaveCount(1);
    expect($listed->first()->name)->toEqual('My Accounts');
});
