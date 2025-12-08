<?php

declare(strict_types=1);

use App\Filament\Pages\PhpInsights;
use App\Models\User;
use App\Services\PhpInsightsService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

uses(RefreshDatabase::class);
uses()
    ->afterEach(fn (): void => Mockery::close());

function mockInsightsService(?array $report = null, ?int $times = null): PhpInsightsService
{
    $mock = Mockery::mock(PhpInsightsService::class);

    $mock->shouldReceive('analyze')
        ->times($times ?? 1)
        ->andReturn($report ?? [
            'summary' => [
                'code' => 72.2,
                'complexity' => 88.5,
                'architecture' => 52.9,
                'style' => 80.7,
            ],
            'issues' => [
                'code' => [],
                'complexity' => [],
                'architecture' => [],
                'style' => [],
                'security' => [],
            ],
        ]);

    app()->instance(PhpInsightsService::class, $mock);

    return $mock;
}

it('shows the insights page for owners and admins', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $user->switchTeam($user->ownedTeams()->first());

    mockInsightsService(times: 1);

    $this->actingAs($user);
    Filament::setTenant($user->currentTeam);

    \Pest\Livewire\livewire(PhpInsights::class)
        ->assertSuccessful()
        ->assertSee(__('app.sections.insights_overview'));
});

it('refreshes the report and notifies the user', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $user->switchTeam($user->ownedTeams()->first());

    mockInsightsService(times: 2);

    $this->actingAs($user);
    Filament::setTenant($user->currentTeam);

    \Pest\Livewire\livewire(PhpInsights::class)
        ->call('refreshReport')
        ->assertNotified(__('app.notifications.insights_refreshed'));
});
