<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Note;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

final class RecentActivity extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Recent activity';

    public function table(Table $table): Table
    {
        return $table
            ->heading(self::$heading)
            ->query(
                Note::query()
                    ->with(['creator'])
                    ->latest('created_at')
            )
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5, 10])
            ->columns([
                TextColumn::make('title')
                    ->label('Update')
                    ->wrap()
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('creator.name')
                    ->label('By')
                    ->wrap()
                    ->placeholder('System')
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->formatStateUsing(fn (Note $record): string => $record->categoryLabel())
                    ->color('gray'),
                TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->sortable(),
            ])
            ->emptyStateHeading('No recent updates')
            ->emptyStateDescription('Log notes on leads, companies, or tasks to see them here.');
    }
}
