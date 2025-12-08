<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($this->user);
    Filament::setTenant($this->user->personalTeam());
});

it('can render the index page', function (): void {
    livewire(App\Filament\Resources\KnowledgeTemplateResponseResource\Pages\ListKnowledgeTemplateResponses::class)
        ->assertOk();
});

it('can render `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\KnowledgeTemplateResponseResource\Pages\ListKnowledgeTemplateResponses::class)
        ->assertCanRenderTableColumn($column);
})->with(['title', 'category.name', 'visibility', 'is_active']);

it('cannot render `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\KnowledgeTemplateResponseResource\Pages\ListKnowledgeTemplateResponses::class)
        ->assertCanNotRenderTableColumn($column);
})->with(['updated_at']);

it('has `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\KnowledgeTemplateResponseResource\Pages\ListKnowledgeTemplateResponses::class)
        ->assertTableColumnExists($column);
})->with(['title', 'category.name', 'visibility', 'is_active', 'updated_at']);

it('shows `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\KnowledgeTemplateResponseResource\Pages\ListKnowledgeTemplateResponses::class)
        ->assertTableColumnVisible($column);
})->with(['title', 'category.name', 'visibility', 'is_active', 'updated_at']);

it('can sort `:dataset` column', function (string $column): void {
    $records = App\Models\KnowledgeTemplateResponse::factory(3)->for($this->user->personalTeam(), 'team')->create();

    livewire(App\Filament\Resources\KnowledgeTemplateResponseResource\Pages\ListKnowledgeTemplateResponses::class)
        ->sortTable($column)
        ->assertCanSeeTableRecords($records->sortBy($column), inOrder: true)
        ->sortTable($column, 'desc')
        ->assertCanSeeTableRecords($records->sortByDesc($column), inOrder: true);
})->with(['updated_at']);

it('can search `:dataset` column', function (string $column): void {
    $records = App\Models\KnowledgeTemplateResponse::factory(3)->for($this->user->personalTeam(), 'team')->create();
    $search = data_get($records->first(), $column);

    livewire(App\Filament\Resources\KnowledgeTemplateResponseResource\Pages\ListKnowledgeTemplateResponses::class)
        ->searchTable($search instanceof BackedEnum ? $search->value : $search)
        ->assertCanSeeTableRecords($records->filter(fn (Illuminate\Database\Eloquent\Model $record): bool => data_get($record, $column) === $search))
        ->assertCanNotSeeTableRecords($records->filter(fn (Illuminate\Database\Eloquent\Model $record): bool => data_get($record, $column) !== $search));
})->with(['title']);

it('cannot display trashed records by default', function (): void {
    $records = App\Models\KnowledgeTemplateResponse::factory()->count(4)->for($this->user->personalTeam(), 'team')->create();
    $trashedRecords = App\Models\KnowledgeTemplateResponse::factory()->trashed()->count(6)->for($this->user->personalTeam(), 'team')->create();

    livewire(App\Filament\Resources\KnowledgeTemplateResponseResource\Pages\ListKnowledgeTemplateResponses::class)
        ->assertCanSeeTableRecords($records)
        ->assertCanNotSeeTableRecords($trashedRecords)
        ->assertCountTableRecords(4);
});

it('can paginate records', function (): void {
    $records = App\Models\KnowledgeTemplateResponse::factory(20)->for($this->user->personalTeam(), 'team')->create();

    livewire(App\Filament\Resources\KnowledgeTemplateResponseResource\Pages\ListKnowledgeTemplateResponses::class)
        ->assertCanSeeTableRecords($records->take(10), inOrder: true)
        ->call('gotoPage', 2)
        ->assertCanSeeTableRecords($records->skip(10)->take(10), inOrder: true);
});

it('has `:dataset` filter', function (string $filter): void {
    livewire(App\Filament\Resources\KnowledgeTemplateResponseResource\Pages\ListKnowledgeTemplateResponses::class)
        ->assertTableFilterExists($filter);
})->with(['category_id', 'visibility', 'is_active', 'trashed']);
