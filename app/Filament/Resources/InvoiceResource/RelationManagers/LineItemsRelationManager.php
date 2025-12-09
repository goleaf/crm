<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class LineItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'lineItems';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-list-bullet';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')
                    ->required(),
                \Filament\Forms\Components\Textarea::make('description')
                    ->rows(2)
                    ->columnSpanFull(),
                \Filament\Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->minValue(0.01)
                    ->step(0.01),
                \Filament\Forms\Components\TextInput::make('unit_price')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->step(0.01),
                \Filament\Forms\Components\TextInput::make('tax_rate')
                    ->label('Tax %')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.01),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money(fn (\App\Models\InvoiceLineItem $record): string => $record->invoice->currency_code ?? 'USD'),
                TextColumn::make('tax_rate')
                    ->label('Tax %')
                    ->formatStateUsing(fn (float|int|string|null $state): string => number_format((float) $state, 2) . ' %'),
                TextColumn::make('line_total')
                    ->label('Line Total')
                    ->money(fn (\App\Models\InvoiceLineItem $record): string => $record->invoice->currency_code ?? 'USD'),
            ])
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-plus')->size(Size::Small),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
