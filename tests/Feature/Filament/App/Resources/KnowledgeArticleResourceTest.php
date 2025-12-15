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
    livewire(App\Filament\Resources\KnowledgeArticleResource\Pages\ListKnowledgeArticles::class)
        ->assertOk();
});

it('can render the view page', function (): void {
    $record = App\Models\KnowledgeArticle::factory()->for($this->user->personalTeam(), 'team')->create();

    livewire(App\Filament\Resources\KnowledgeArticleResource\Pages\ViewKnowledgeArticle::class, ['record' => $record->getKey()])
        ->assertOk();
});

it('can render `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\KnowledgeArticleResource\Pages\ListKnowledgeArticles::class)
        ->assertCanRenderTableColumn($column);
})->with(['title', 'status', 'visibility', 'category.name', 'author.name', 'view_count']);

it('cannot render `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\KnowledgeArticleResource\Pages\ListKnowledgeArticles::class)
        ->assertCanNotRenderTableColumn($column);
})->with(['ratings_avg', 'published_at', 'is_featured', 'updated_at']);

it('has `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\KnowledgeArticleResource\Pages\ListKnowledgeArticles::class)
        ->assertTableColumnExists($column);
})->with(['title', 'status', 'visibility', 'category.name', 'author.name', 'ratings_avg', 'view_count', 'published_at', 'is_featured', 'updated_at']);

it('shows `:dataset` column', function (string $column): void {
    livewire(App\Filament\Resources\KnowledgeArticleResource\Pages\ListKnowledgeArticles::class)
        ->assertTableColumnVisible($column);
})->with(['title', 'status', 'visibility', 'category.name', 'author.name', 'ratings_avg', 'view_count', 'published_at', 'is_featured', 'updated_at']);

it('can sort `:dataset` column', function (string $column): void {
    $records = App\Models\KnowledgeArticle::factory(3)->for($this->user->personalTeam(), 'team')->create();

    $sortingKey = data_get($records->first(), $column) instanceof BackedEnum
        ? fn (Illuminate\Database\Eloquent\Model $record) => data_get($record, $column)?->value
        : $column;

    livewire(App\Filament\Resources\KnowledgeArticleResource\Pages\ListKnowledgeArticles::class)
        ->sortTable($column)
        ->assertCanSeeTableRecords($records->sortBy($sortingKey), inOrder: true)
        ->sortTable($column, 'desc')
        ->assertCanSeeTableRecords($records->sortByDesc($sortingKey), inOrder: true);
})->with(['status', 'category.name', 'ratings_avg', 'view_count', 'published_at', 'updated_at']);

it('can search `:dataset` column', function (string $column): void {
    $records = App\Models\KnowledgeArticle::factory(3)->for($this->user->personalTeam(), 'team')->create();
    $search = data_get($records->first(), $column);

    livewire(App\Filament\Resources\KnowledgeArticleResource\Pages\ListKnowledgeArticles::class)
        ->searchTable($search instanceof BackedEnum ? $search->value : $search)
        ->assertCanSeeTableRecords($records->filter(fn (Illuminate\Database\Eloquent\Model $record): bool => data_get($record, $column) === $search))
        ->assertCanNotSeeTableRecords($records->filter(fn (Illuminate\Database\Eloquent\Model $record): bool => data_get($record, $column) !== $search));
})->with(['title']);

it('cannot display trashed records by default', function (): void {
    $records = App\Models\KnowledgeArticle::factory()->count(4)->for($this->user->personalTeam(), 'team')->create();
    $trashedRecords = App\Models\KnowledgeArticle::factory()->trashed()->count(6)->for($this->user->personalTeam(), 'team')->create();

    livewire(App\Filament\Resources\KnowledgeArticleResource\Pages\ListKnowledgeArticles::class)
        ->assertCanSeeTableRecords($records)
        ->assertCanNotSeeTableRecords($trashedRecords)
        ->assertCountTableRecords(4);
});

it('can paginate records', function (): void {
    $records = App\Models\KnowledgeArticle::factory(20)->for($this->user->personalTeam(), 'team')->create();

    livewire(App\Filament\Resources\KnowledgeArticleResource\Pages\ListKnowledgeArticles::class)
        ->assertCanSeeTableRecords($records->take(10), inOrder: true)
        ->call('gotoPage', 2)
        ->assertCanSeeTableRecords($records->skip(10)->take(10), inOrder: true);
});

it('has `:dataset` filter', function (string $filter): void {
    livewire(App\Filament\Resources\KnowledgeArticleResource\Pages\ListKnowledgeArticles::class)
        ->assertTableFilterExists($filter);
})->with(['status', 'visibility', 'category_id', 'tags', 'trashed']);
