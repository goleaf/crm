<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($this->user);
    Filament::setTenant($this->user->personalTeam());
});

it('can render the index page', function (): void {
    livewire(App\Filament\Resources\Accounts\Pages\ListAccounts::class)
        ->assertOk();
});

it('can render the view page', function (): void {
    $record = App\Models\Account::factory()->for($this->user->personalTeam(), 'team')->create();

    livewire(App\Filament\Resources\Accounts\Pages\ViewAccount::class, ['record' => $record->getKey()])
        ->assertOk();
});

it('can render `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\Accounts\Pages\ListAccounts::class)
        ->assertCanRenderTableColumn($column);
})->with(['name', 'parent.name', 'type', 'industry', 'owner.name', 'billing_address']);

it('cannot render `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\Accounts\Pages\ListAccounts::class)
        ->assertCanNotRenderTableColumn($column);
})->with(['annual_revenue', 'employee_count', 'assignedTo.name', 'children_count', 'shipping_address', 'website', 'currency', 'created_at', 'updated_at']);

it('has `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\Accounts\Pages\ListAccounts::class)
        ->assertTableColumnExists($column);
})->with([
    'name',
    'parent.name',
    'type',
    'industry',
    'annual_revenue',
    'employee_count',
    'owner.name',
    'assignedTo.name',
    'children_count',
    'billing_address',
    'shipping_address',
    'website',
    'currency',
    'created_at',
    'updated_at',
]);

it('shows `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\Accounts\Pages\ListAccounts::class)
        ->assertTableColumnVisible($column);
})->with([
    'name',
    'parent.name',
    'type',
    'industry',
    'annual_revenue',
    'employee_count',
    'owner.name',
    'assignedTo.name',
    'children_count',
    'billing_address',
    'shipping_address',
    'website',
    'currency',
    'created_at',
    'updated_at',
]);

it('can sort `:dataset` column', function (string $column): void {
    $records = App\Models\Account::factory(3)->for($this->user->personalTeam(), 'team')->create();

    $sortingKey = data_get($records->first(), $column) instanceof BackedEnum
        ? fn (Illuminate\Database\Eloquent\Model $record) => data_get($record, $column)?->value
        : $column;

    livewire(App\Filament\Resources\Accounts\Pages\ListAccounts::class)
        ->sortTable($column)
        ->assertCanSeeTableRecords($records->sortBy($sortingKey), inOrder: true)
        ->sortTable($column, 'desc')
        ->assertCanSeeTableRecords($records->sortByDesc($sortingKey), inOrder: true);
})->with(['name', 'parent.name', 'type', 'industry', 'owner.name', 'created_at', 'updated_at']);

it('can search `:dataset` column', function (string $column): void {
    $records = App\Models\Account::factory(3)->for($this->user->personalTeam(), 'team')->create();
    $search = data_get($records->first(), $column);

    livewire(App\Filament\Resources\Accounts\Pages\ListAccounts::class)
        ->searchTable($search instanceof BackedEnum ? $search->value : $search)
        ->assertCanSeeTableRecords($records->filter(fn (Illuminate\Database\Eloquent\Model $record) => data_get($record, $column) === $search))
        ->assertCanNotSeeTableRecords($records->filter(fn (Illuminate\Database\Eloquent\Model $record) => data_get($record, $column) !== $search));
})->with(['name', 'industry', 'owner.name']);

it('can paginate records', function (): void {
    $records = App\Models\Account::factory(20)->for($this->user->personalTeam(), 'team')->create();

    livewire(App\Filament\Resources\Accounts\Pages\ListAccounts::class)
        ->assertCanSeeTableRecords($records->take(10), inOrder: true)
        ->call('gotoPage', 2)
        ->assertCanSeeTableRecords($records->skip(10)->take(10), inOrder: true);
});

it('can bulk delete records', function (): void {
    $records = App\Models\Account::factory(5)->for($this->user->personalTeam(), 'team')->create();

    livewire(App\Filament\Resources\Accounts\Pages\ListAccounts::class)
        ->assertCanSeeTableRecords($records)
        ->selectTableRecords($records)
        ->callAction([['name' => 'delete', 'context' => ['table' => true, 'bulk' => true]]])
        ->assertNotified()
        ->assertCanNotSeeTableRecords($records);

    $records->each(function (App\Models\Account $record): void {
        $this->assertDatabaseMissing('accounts', ['id' => $record->getKey()]);
    });
});

it('has `:dataset` filter', function (string $filter): void {
    livewire(App\Filament\Resources\Accounts\Pages\ListAccounts::class)
        ->assertTableFilterExists($filter);
})->with(['type', 'industry', 'currency']);
