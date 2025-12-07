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
    livewire(App\Filament\Resources\KnowledgeTagResource\Pages\ListKnowledgeTags::class)
        ->assertOk();
});

it('can render `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\KnowledgeTagResource\Pages\ListKnowledgeTags::class)
        ->assertCanRenderTableColumn($column);
})->with(['name', 'description']);

it('cannot render `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\KnowledgeTagResource\Pages\ListKnowledgeTags::class)
        ->assertCanNotRenderTableColumn($column);
})->with(['updated_at']);

it('has `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\KnowledgeTagResource\Pages\ListKnowledgeTags::class)
        ->assertTableColumnExists($column);
})->with(['name', 'description', 'updated_at']);

it('shows `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\KnowledgeTagResource\Pages\ListKnowledgeTags::class)
        ->assertTableColumnVisible($column);
})->with(['name', 'description', 'updated_at']);

it('can sort `:dataset` column', function (string $column): void {
    $records = App\Models\KnowledgeTag::factory(3)->for($this->user->personalTeam(), 'team')->create();

    livewire(App\Filament\Resources\KnowledgeTagResource\Pages\ListKnowledgeTags::class)
        ->sortTable($column)
        ->assertCanSeeTableRecords($records->sortBy($column), inOrder: true)
        ->sortTable($column, 'desc')
        ->assertCanSeeTableRecords($records->sortByDesc($column), inOrder: true);
})->with(['name', 'updated_at']);

it('can search `:dataset` column', function (string $column): void {
    $records = App\Models\KnowledgeTag::factory(3)->for($this->user->personalTeam(), 'team')->create();
    $search = data_get($records->first(), $column);

    livewire(App\Filament\Resources\KnowledgeTagResource\Pages\ListKnowledgeTags::class)
        ->searchTable($search instanceof BackedEnum ? $search->value : $search)
        ->assertCanSeeTableRecords($records->filter(fn (Illuminate\Database\Eloquent\Model $record) => data_get($record, $column) === $search))
        ->assertCanNotSeeTableRecords($records->filter(fn (Illuminate\Database\Eloquent\Model $record) => data_get($record, $column) !== $search));
})->with(['name']);

it('cannot display trashed records by default', function (): void {
    $records = App\Models\KnowledgeTag::factory()->count(4)->for($this->user->personalTeam(), 'team')->create();
    $trashedRecords = App\Models\KnowledgeTag::factory()->trashed()->count(6)->for($this->user->personalTeam(), 'team')->create();

    livewire(App\Filament\Resources\KnowledgeTagResource\Pages\ListKnowledgeTags::class)
        ->assertCanSeeTableRecords($records)
        ->assertCanNotSeeTableRecords($trashedRecords)
        ->assertCountTableRecords(4);
});

it('can paginate records', function (): void {
    $records = App\Models\KnowledgeTag::factory(20)->for($this->user->personalTeam(), 'team')->create();

    livewire(App\Filament\Resources\KnowledgeTagResource\Pages\ListKnowledgeTags::class)
        ->assertCanSeeTableRecords($records->take(10), inOrder: true)
        ->call('gotoPage', 2)
        ->assertCanSeeTableRecords($records->skip(10)->take(10), inOrder: true);
});

it('has `:dataset` filter', function (string $filter): void {
    livewire(App\Filament\Resources\KnowledgeTagResource\Pages\ListKnowledgeTags::class)
        ->assertTableFilterExists($filter);
})->with(['trashed']);
