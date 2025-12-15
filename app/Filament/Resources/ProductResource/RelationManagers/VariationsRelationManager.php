<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class VariationsRelationManager extends RelationManager
{
    protected static string $relationship = 'variations';

    protected static ?string $title = 'Product Variations';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('sku')
                ->label('SKU')
                ->required()
                ->maxLength(120),
            TextInput::make('price')
                ->numeric()
                ->required()
                ->minValue(0),
            TextInput::make('currency_code')
                ->label('Currency')
                ->maxLength(3)
                ->default('USD'),
            Checkbox::make('is_default')
                ->label('Default Variation'),
            Checkbox::make('track_inventory')
                ->label('Track Inventory')
                ->reactive(),
            TextInput::make('inventory_quantity')
                ->label('Inventory Quantity')
                ->numeric()
                ->default(0)
                ->visible(fn ($get) => $get('track_inventory')),
            KeyValue::make('options')
                ->label('Variation Options')
                ->keyLabel('Attribute')
                ->valueLabel('Value')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('price')
                    ->money(fn ($record) => $record->currency_code ?? 'USD')
                    ->sortable(),
                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
                TextColumn::make('inventory_quantity')
                    ->label('Stock')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
