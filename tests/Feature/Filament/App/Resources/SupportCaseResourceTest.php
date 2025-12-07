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
    livewire(App\Filament\Resources\SupportCaseResource\Pages\ListSupportCases::class)
        ->assertOk();
});

it('can render the view page', function (): void {
    $record = App\Models\SupportCase::factory()->for($this->user->personalTeam())->create();

    livewire(App\Filament\Resources\SupportCaseResource\Pages\ViewSupportCase::class, ['record' => $record->getKey()])
        ->assertOk();
});

it('can render `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\SupportCaseResource\Pages\ListSupportCases::class)
        ->assertCanRenderTableColumn($column);
})->with(['case_number', 'subject', 'status', 'priority', 'type', 'channel', 'company.name', 'assignee.name']);

it('cannot render `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\SupportCaseResource\Pages\ListSupportCases::class)
        ->assertCanNotRenderTableColumn($column);
})->with(['queue', 'contact.name', 'assignedTeam.name', 'sla_due_at', 'first_response_at', 'resolved_at', 'deleted_at']);

it('has `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\SupportCaseResource\Pages\ListSupportCases::class)
        ->assertTableColumnExists($column);
})->with([
    'case_number',
    'subject',
    'status',
    'priority',
    'type',
    'channel',
    'queue',
    'company.name',
    'contact.name',
    'assignee.name',
    'assignedTeam.name',
    'sla_due_at',
    'first_response_at',
    'resolved_at',
    'created_at',
    'updated_at',
    'deleted_at',
]);

it('shows `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\SupportCaseResource\Pages\ListSupportCases::class)
        ->assertTableColumnVisible($column);
})->with([
    'case_number',
    'subject',
    'status',
    'priority',
    'type',
    'channel',
    'queue',
    'company.name',
    'contact.name',
    'assignee.name',
    'assignedTeam.name',
    'sla_due_at',
    'first_response_at',
    'resolved_at',
    'created_at',
    'updated_at',
    'deleted_at',
]);

it('can sort `:dataset` column', function (string $column): void {
    $records = App\Models\SupportCase::factory(3)->for($this->user->personalTeam())->create();

    $sortingKey = data_get($records->first(), $column) instanceof BackedEnum
        ? fn (Illuminate\Database\Eloquent\Model $record) => data_get($record, $column)?->value
        : $column;

    livewire(App\Filament\Resources\SupportCaseResource\Pages\ListSupportCases::class)
        ->sortTable($column)
        ->assertCanSeeTableRecords($records->sortBy($sortingKey), inOrder: true)
        ->sortTable($column, 'desc')
        ->assertCanSeeTableRecords($records->sortByDesc($sortingKey), inOrder: true);
})->with(['case_number', 'created_at', 'updated_at', 'deleted_at']);

it('can search `:dataset` column', function (string $column): void {
    $records = App\Models\SupportCase::factory(3)->for($this->user->personalTeam())->create();
    $search = data_get($records->first(), $column);

    livewire(App\Filament\Resources\SupportCaseResource\Pages\ListSupportCases::class)
        ->searchTable($search instanceof BackedEnum ? $search->value : $search)
        ->assertCanSeeTableRecords($records->filter(fn (Illuminate\Database\Eloquent\Model $record) => data_get($record, $column) === $search))
        ->assertCanNotSeeTableRecords($records->filter(fn (Illuminate\Database\Eloquent\Model $record) => data_get($record, $column) !== $search));
})->with(['case_number', 'subject']);

it('cannot display trashed records by default', function (): void {
    $records = App\Models\SupportCase::factory()->count(4)->for($this->user->personalTeam())->create();
    $trashedRecords = App\Models\SupportCase::factory()->trashed()->count(6)->for($this->user->personalTeam())->create();

    livewire(App\Filament\Resources\SupportCaseResource\Pages\ListSupportCases::class)
        ->assertCanSeeTableRecords($records)
        ->assertCanNotSeeTableRecords($trashedRecords)
        ->assertCountTableRecords(4);
});

it('can paginate records', function (): void {
    $records = App\Models\SupportCase::factory(20)->for($this->user->personalTeam())->create();

    livewire(App\Filament\Resources\SupportCaseResource\Pages\ListSupportCases::class)
        ->assertCanSeeTableRecords($records->take(10), inOrder: true)
        ->call('gotoPage', 2)
        ->assertCanSeeTableRecords($records->skip(10)->take(10), inOrder: true);
});

it('can bulk delete records', function (): void {
    $records = App\Models\SupportCase::factory(5)->for($this->user->personalTeam())->create();

    livewire(App\Filament\Resources\SupportCaseResource\Pages\ListSupportCases::class)
        ->assertCanSeeTableRecords($records)
        ->selectTableRecords($records)
        ->callAction([['name' => 'delete', 'context' => ['table' => true, 'bulk' => true]]])
        ->assertNotified()
        ->assertCanNotSeeTableRecords($records);

    $this->assertSoftDeleted($records);
});

it('has `:dataset` filter', function (string $filter): void {
    livewire(App\Filament\Resources\SupportCaseResource\Pages\ListSupportCases::class)
        ->assertTableFilterExists($filter);
})->with([
    'status',
    'priority',
    'type',
    'channel',
    'queue',
    'company_id',
    'assigned_to_id',
    'assigned_team_id',
    'creation_source',
    'overdue_sla',
    'trashed',
]);
