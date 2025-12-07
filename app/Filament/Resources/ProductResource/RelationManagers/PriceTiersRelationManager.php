<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class PriceTiersRelationManager extends RelationManager
{
    protected static string $relationship = 'priceTiers';

    protected static ?string $title = 'Pricing Tiers';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')
                ->maxLength(120)
                ->label('Name'),
            TextInput::make('min_quantity')
                ->label('Min Qty')
                ->numeric()
                ->required()
                ->default(1)
                ->minValue(1),
            TextInput::make('max_quantity')
                ->label('Max Qty')
                ->numeric()
                ->minValue(1),
            TextInput::make('price')
                ->label('Unit Price')
                ->numeric()
                ->required()
                ->minValue(0)
                ->prefix('$'),
            TextInput::make('currency_code')
                ->label('Currency')
                ->maxLength(3)
                ->default('USD'),
            DatePicker::make('starts_at')
                ->label('Starts'),
            DatePicker::make('ends_at')
                ->label('Ends'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label('Name')
                    ->toggleable(),
                TextColumn::make('min_quantity')
                    ->label('Min')
                    ->sortable(),
                TextColumn::make('max_quantity')
                    ->label('Max')
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Price')
                    ->money(fn ($record) => $record->currency_code ?? 'USD')
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->date()
                    ->label('Starts'),
                TextColumn::make('ends_at')
                    ->date()
                    ->label('Ends'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(fn (array $data): array => [
                        ...$data,
                        'team_id' => $this->ownerRecord->team_id,
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
