<?php

declare(strict_types=1);

use App\Models\Delivery;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($this->user);
    Filament::setTenant($this->user->personalTeam());
});

it('can render the index page', function (): void {
    livewire(App\Filament\Resources\DeliveryResource\Pages\ListDeliveries::class)
        ->assertOk();
});

it('has `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\DeliveryResource\Pages\ListDeliveries::class)
        ->assertTableColumnExists($column);
})->with(['number', 'order.number', 'status', 'carrier', 'tracking_number', 'scheduled_delivery_at', 'delivered_at']);

it('shows `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\DeliveryResource\Pages\ListDeliveries::class)
        ->assertTableColumnVisible($column);
})->with(['number', 'status', 'carrier', 'tracking_number']);

it('can paginate records', function (): void {
    $records = Delivery::factory(20)
        ->for($this->user->personalTeam())
        ->state(['creator_id' => $this->user->id])
        ->create();

    livewire(App\Filament\Resources\DeliveryResource\Pages\ListDeliveries::class)
        ->assertCanSeeTableRecords($records->take(10))
        ->call('gotoPage', 2)
        ->assertCanSeeTableRecords($records->skip(10)->take(10));
});
