<?php

declare(strict_types=1);

use App\Filament\Resources\PeopleResource\Pages\ListPeopleActivities;
use App\Models\People;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();
    $this->team->users()->attach($this->user);

    actingAs($this->user);
    $this->user->switchTeam($this->team);
});

it('shows activity log entries for a person record', function (): void {
    $person = People::factory()->create([
        'team_id' => $this->team->getKey(),
        'department' => 'Sales',
    ]);

    $person->activities()->create([
        'team_id' => $this->team->getKey(),
        'causer_id' => $this->user->getKey(),
        'event' => 'updated',
        'changes' => [
            'old' => ['department' => 'Sales'],
            'attributes' => ['department' => 'Marketing'],
        ],
    ]);

    Livewire::test(ListPeopleActivities::class, ['record' => $person->getKey()])
        ->assertSuccessful()
        ->assertSee($this->user->name)
        ->assertSee('Marketing')
        ->assertSee('Sales');
});
