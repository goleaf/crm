<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\People;
use App\Models\Team;

it('links people to a group', function (): void {
    $team = Team::factory()->create();

    $group = Group::factory()->for($team)->create();
    $person = People::factory()->for($team)->create();

    $group->people()->attach($person);

    expect($group->people)->toHaveCount(1);
    expect($group->people->first()->is($person))->toBeTrue();
    expect($person->groups)->toHaveCount(1);
    expect($person->groups->first()->is($group))->toBeTrue();
});