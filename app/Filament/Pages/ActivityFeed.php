<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class ActivityFeed extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rss';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.activity-feed';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.activity_feed');
    }

    public function getTitle(): string
    {
        return __('app.pages.activity_feed.title');
    }

    public function table(Table $table): Table
    {
        /** @var \App\Services\Activity\ActivityFeedService $service */
        $service = resolve(\App\Services\Activity\ActivityFeedService::class);
        $teamId = filament()->getTenant()->id;

        // The ActivityFeedService returns a LengthAwarePaginator, but for Filament tables
        // we generally need a Builder or we need to use a custom data source.
        // However, looking at the steering docs, the UnionPaginator works by returning a
        // result that works with pagination.
        // Filament's $table->query() expects a Builder.
        // The steering doc shows:
        // return $table->query($this->buildQuery($teamId))...
        // And the steering doc example for Filament Integration shows:
        // DB::query()->fromSub($tasks->union($notes), 'activities');
        //
        // Wait, the ActivityFeedService returns a LengthAwarePaginator (executed query),
        // NOT a Builder. Filament Tables need a Builder to handle sorting/filtering efficiently
        // before pagination, OR we need to use a custom way to feed data.
        //
        // Let's re-read the Service. The service executes `->paginate($perPage)`.
        // This means the service performs the query.
        // Filament tables typically take a Query Builder.
        //
        // If I use the service as is, I can't easily plug it into `table->query()`.
        //
        // Let's look at the steering doc again.
        // The steering doc example "Filament Integration" shows `buildQuery` returning `DB::query()->fromSub(...)`.
        // This returns a Builder.
        //
        // The `ActivityFeedService` methods `getTeamActivity` return `LengthAwarePaginator`.
        // This implies the service executes the query.
        //
        // To integrate with Filament while keeping the Service Pattern, I should probably
        // add a method to `ActivityFeedService` that returns the `Builder` BEFORE pagination,
        // OR modifying the Page to use the `Builder` logic.
        //
        // Given the instructions "fully integrate" and "use service", I should expose a method
        // in `ActivityFeedService` that returns the Builder, so Filament can control pagination/sorting.
        //
        // Let's check `ActivityFeedService` again. It has `private function buildTasksQuery` etc.
        // I should probably add a public `getTeamActivityQuery(int $teamId): Builder` to the service.
        //
        // For now, I will modify the Page to use a new method I will add to the Service: `getTeamActivityQuery`.

        return $table
            ->query($service->getTeamActivityQuery($teamId))
            ->columns([
                Tables\Columns\IconColumn::make('icon')
                    ->label('')
                    ->icon(fn ($record) => $record->icon)
                    ->color(fn ($record) => $record->color)
                    ->size(Tables\Columns\IconColumn\IconColumnSize::Medium),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('app.labels.name'))
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->name)
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('activity_type')
                    ->label(__('app.labels.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("app.activity_types.{$state}"))
                    ->color(fn ($record) => $record->color),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('app.labels.description'))
                    ->limit(100)
                    ->toggleable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->description(fn ($record): string => $record->created_at->format('l, F j, Y')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('activity_type')
                    ->label(__('app.labels.type'))
                    ->options([
                        'task' => __('app.activity_types.task'),
                        'note' => __('app.activity_types.note'),
                        'opportunity' => __('app.activity_types.opportunity'),
                        'case' => __('app.activity_types.case'),
                    ])
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label(__('app.actions.view'))
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => $record->url)
                    ->openUrlInNewTab(false),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([15, 25, 50, 100])
            ->poll('30s')
            ->striped();
    }

    #[\Deprecated(message: 'Use ActivityFeedService directly')]
    protected function getTableQuery(): Builder
    {
        // validation required by interface but unused due to ->query() call above
        return DB::query();
    }
}
