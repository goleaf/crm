<?php

declare(strict_types=1);

use App\Filament\Pages\SeederGenerator;
use App\Models\User;
use App\Services\SeederGeneratorService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    Mockery::close();
});

it('shows the seeder generator form for admins or owners', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $user->switchTeam($user->ownedTeams()->first());

    $this->actingAs($user);
    Filament::setTenant($user->currentTeam);

    \Pest\Livewire\livewire(SeederGenerator::class)
        ->assertSuccessful();
});

it('runs the generator with provided model options', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $user->switchTeam($user->ownedTeams()->first());

    $mock = Mockery::mock(SeederGeneratorService::class);
    $mock->shouldReceive('modelOptions')->andReturn(['Lead' => \App\Models\Lead::class]);
    $mock->shouldReceive('tableOptions')->andReturn([]);
    $mock->shouldReceive('buildOptions')
        ->once()
        ->andReturn(['--model-mode' => true, '--models' => 'Lead']);
    $mock->shouldReceive('run')
        ->once()
        ->andReturn('Generated Lead seeder');

    app()->instance(SeederGeneratorService::class, $mock);

    $this->actingAs($user);
    Filament::setTenant($user->currentTeam);

    \Pest\Livewire\livewire(SeederGenerator::class)
        ->set('data', [
            'mode' => 'model',
            'models' => ['Lead'],
            'include_relations' => false,
            'order_direction' => 'asc',
            'add_to_database_seeder' => true,
        ])
        ->call('save')
        ->assertNotified(__('app.messages.seed_generation_success'));
});
