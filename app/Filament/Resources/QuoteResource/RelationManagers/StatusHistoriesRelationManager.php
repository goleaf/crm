<?php

declare(strict_types=1);

namespace App\Filament\Resources\QuoteResource\RelationManagers;

use App\Enums\QuoteStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class StatusHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistories';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-clock';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('to_status')
            ->columns([
                BadgeColumn::make('from_status')
                    ->label('From')
                    ->colors([
                        'gray',
                        'primary' => QuoteStatus::SENT->value,
                        'success' => QuoteStatus::ACCEPTED->value,
                        'danger' => QuoteStatus::REJECTED->value,
                    ])
                    ->formatStateUsing(fn (?QuoteStatus $state): string => $state?->getLabel() ?? '—'),
                BadgeColumn::make('to_status')
                    ->label('To')
                    ->colors([
                        'gray',
                        'primary' => QuoteStatus::SENT->value,
                        'success' => QuoteStatus::ACCEPTED->value,
                        'danger' => QuoteStatus::REJECTED->value,
                    ])
                    ->formatStateUsing(fn (QuoteStatus $state): string => $state->getLabel()),
                TextColumn::make('changedBy.name')
                    ->label('By')
                    ->placeholder('System'),
                TextColumn::make('note')
                    ->label('Note')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Changed At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->paginated(false)
            ->headerActions([])
            ->recordActions([])
            ->bulkActions([]);
    }
}
