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
    livewire(App\Filament\Resources\KnowledgeFaqResource\Pages\ListKnowledgeFaqs::class)
        ->assertOk();
});

it('can render `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\KnowledgeFaqResource\Pages\ListKnowledgeFaqs::class)
        ->assertCanRenderTableColumn($column);
})->with(['question', 'status', 'visibility', 'article.title', 'position']);

it('cannot render `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\KnowledgeFaqResource\Pages\ListKnowledgeFaqs::class)
        ->assertCanNotRenderTableColumn($column);
})->with(['updated_at']);

it('has `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\KnowledgeFaqResource\Pages\ListKnowledgeFaqs::class)
        ->assertTableColumnExists($column);
})->with(['question', 'status', 'visibility', 'article.title', 'position', 'updated_at']);

it('shows `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\KnowledgeFaqResource\Pages\ListKnowledgeFaqs::class)
        ->assertTableColumnVisible($column);
})->with(['question', 'status', 'visibility', 'article.title', 'position', 'updated_at']);

it('can sort `:dataset` column', function (string $column): void {
    $records = App\Models\KnowledgeFaq::factory(3)
        ->for(App\Models\KnowledgeArticle::factory()->for($this->user->personalTeam(), 'team'), 'article')
        ->create();

    $sortingKey = data_get($records->first(), $column) instanceof BackedEnum
        ? fn (Illuminate\Database\Eloquent\Model $record) => data_get($record, $column)?->value
        : $column;

    livewire(App\Filament\Resources\KnowledgeFaqResource\Pages\ListKnowledgeFaqs::class)
        ->sortTable($column)
        ->assertCanSeeTableRecords($records->sortBy($sortingKey), inOrder: true)
        ->sortTable($column, 'desc')
        ->assertCanSeeTableRecords($records->sortByDesc($sortingKey), inOrder: true);
})->with(['position', 'updated_at']);

it('can search `:dataset` column', function (string $column): void {
    $records = App\Models\KnowledgeFaq::factory(3)
        ->for(App\Models\KnowledgeArticle::factory()->for($this->user->personalTeam(), 'team'), 'article')
        ->create();
    $search = data_get($records->first(), $column);

    livewire(App\Filament\Resources\KnowledgeFaqResource\Pages\ListKnowledgeFaqs::class)
        ->searchTable($search instanceof BackedEnum ? $search->value : $search)
        ->assertCanSeeTableRecords($records->filter(fn (Illuminate\Database\Eloquent\Model $record): bool => data_get($record, $column) === $search))
        ->assertCanNotSeeTableRecords($records->filter(fn (Illuminate\Database\Eloquent\Model $record): bool => data_get($record, $column) !== $search));
})->with(['question']);

it('cannot display trashed records by default', function (): void {
    $records = App\Models\KnowledgeFaq::factory()
        ->count(4)
        ->for(App\Models\KnowledgeArticle::factory()->for($this->user->personalTeam(), 'team'), 'article')
        ->create();
    $trashedRecords = App\Models\KnowledgeFaq::factory()
        ->trashed()
        ->count(6)
        ->for(App\Models\KnowledgeArticle::factory()->for($this->user->personalTeam(), 'team'), 'article')
        ->create();

    livewire(App\Filament\Resources\KnowledgeFaqResource\Pages\ListKnowledgeFaqs::class)
        ->assertCanSeeTableRecords($records)
        ->assertCanNotSeeTableRecords($trashedRecords)
        ->assertCountTableRecords(4);
});

it('can paginate records', function (): void {
    $records = App\Models\KnowledgeFaq::factory(20)
        ->for(App\Models\KnowledgeArticle::factory()->for($this->user->personalTeam(), 'team'), 'article')
        ->create();

    livewire(App\Filament\Resources\KnowledgeFaqResource\Pages\ListKnowledgeFaqs::class)
        ->assertCanSeeTableRecords($records->take(10), inOrder: true)
        ->call('gotoPage', 2)
        ->assertCanSeeTableRecords($records->skip(10)->take(10), inOrder: true);
});

it('has `:dataset` filter', function (string $filter): void {
    livewire(App\Filament\Resources\KnowledgeFaqResource\Pages\ListKnowledgeFaqs::class)
        ->assertTableFilterExists($filter);
})->with(['status', 'visibility', 'trashed']);
