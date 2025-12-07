<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use App\Enums\InvoiceStatus;
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
                        'info' => InvoiceStatus::SENT->value,
                        'success' => InvoiceStatus::PAID->value,
                        'danger' => InvoiceStatus::OVERDUE->value,
                    ])
                    ->formatStateUsing(fn (?InvoiceStatus $state): string => $state?->label() ?? '—'),
                BadgeColumn::make('to_status')
                    ->label('To')
                    ->colors([
                        'gray',
                        'info' => InvoiceStatus::SENT->value,
                        'success' => InvoiceStatus::PAID->value,
                        'warning' => InvoiceStatus::PARTIAL->value,
                        'danger' => InvoiceStatus::OVERDUE->value,
                    ])
                    ->formatStateUsing(fn (InvoiceStatus $state): string => $state->label()),
                TextColumn::make('changer.name')
                    ->label('By')
                    ->placeholder('System'),
                TextColumn::make('note')
                    ->label('Note')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Changed At')
                    ->sortable(),
            ])
            ->paginated(false)
            ->headerActions([])
            ->recordActions([])
            ->bulkActions([]);
    }
}
