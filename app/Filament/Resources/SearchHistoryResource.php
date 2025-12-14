<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SearchHistoryResource\Pages;
use App\Models\SearchHistory;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class SearchHistoryResource extends Resource
{
    protected static ?string $model = SearchHistory::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-clock';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.data_management');
    }

    protected static ?int $navigationSort = 60;

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.search_history');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.search_history');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.search_histories');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('query')
                    ->label(__('app.labels.query'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('module')
                    ->label(__('app.labels.module'))
                    ->badge()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('app.labels.user'))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('results_count')
                    ->label(__('app.labels.results_count'))
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('execution_time')
                    ->label(__('app.labels.execution_time'))
                    ->suffix('s')
                    ->numeric(decimalPlaces: 4)
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('searched_at')
                    ->label(__('app.labels.searched_at'))
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn (SearchHistory $record): string => $record->searched_at->format('M j, Y g:i A'),
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('module')
                    ->label(__('app.labels.module'))
                    ->options([
                        'companies' => __('app.labels.companies'),
                        'people' => __('app.labels.people'),
                        'opportunities' => __('app.labels.opportunities'),
                        'tasks' => __('app.labels.tasks'),
                        'support_cases' => __('app.labels.support_cases'),
                    ]),

                Tables\Filters\Filter::make('slow_queries')
                    ->label(__('app.labels.slow_queries'))
                    ->query(fn ($query) => $query->where('execution_time', '>', 1.0)),

                Tables\Filters\Filter::make('no_results')
                    ->label(__('app.labels.no_results'))
                    ->query(fn ($query) => $query->where('results_count', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('searched_at', 'desc')
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSearchHistories::route('/'),
            'view' => Pages\ViewSearchHistory::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }
}
