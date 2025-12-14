<?php

declare(strict_types=1);

namespace App\Filament\Resources\SearchHistoryResource\Pages;

use App\Filament\Resources\SearchHistoryResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\KeyValueEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Schema;

final class ViewSearchHistory extends ViewRecord
{
    protected static string $resource = SearchHistoryResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.labels.search_details'))
                    ->schema([
                        TextEntry::make('query')
                            ->label(__('app.labels.query'))
                            ->copyable(),

                        TextEntry::make('module')
                            ->label(__('app.labels.module'))
                            ->badge(),

                        TextEntry::make('user.name')
                            ->label(__('app.labels.user')),

                        TextEntry::make('results_count')
                            ->label(__('app.labels.results_count'))
                            ->numeric(),

                        TextEntry::make('execution_time')
                            ->label(__('app.labels.execution_time'))
                            ->suffix('s')
                            ->numeric(decimalPlaces: 4),

                        TextEntry::make('searched_at')
                            ->label(__('app.labels.searched_at'))
                            ->dateTime(),
                    ])
                    ->columns(2),

                Section::make(__('app.labels.filters'))
                    ->schema([
                        KeyValueEntry::make('filters')
                            ->label(__('app.labels.applied_filters'))
                            ->keyLabel(__('app.labels.field'))
                            ->valueLabel(__('app.labels.value')),
                    ])
                    ->visible(fn ($record): bool => ! empty($record->filters)),
            ]);
    }
}
