<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Enums\QuoteDiscountType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class DiscountRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'discountRules';

    protected static ?string $title = 'Pricing & Discounts';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            Select::make('scope')
                ->options([
                    'product' => 'Product',
                    'category' => 'Category',
                    'global' => 'Team / Global',
                    'customer' => 'Specific Customer',
                    'customer_group' => 'Customer Group',
                ])
                ->default('product'),
            Select::make('product_category_id')
                ->relationship('category', 'name')
                ->label('Category')
                ->searchable()
                ->preload(),
            Select::make('company_id')
                ->relationship('company', 'name')
                ->label('Customer')
                ->searchable()
                ->preload(),
            Select::make('discount_type')
                ->label('Discount Type')
                ->options([
                    QuoteDiscountType::PERCENT->value => QuoteDiscountType::PERCENT->getLabel(),
                    QuoteDiscountType::FIXED->value => QuoteDiscountType::FIXED->getLabel(),
                ])
                ->required(),
            TextInput::make('discount_value')
                ->label('Discount')
                ->numeric()
                ->minValue(0)
                ->required(),
            TextInput::make('min_quantity')
                ->label('Min Qty')
                ->numeric()
                ->default(1)
                ->minValue(1),
            TextInput::make('max_quantity')
                ->label('Max Qty')
                ->numeric()
                ->minValue(1),
            TextInput::make('priority')
                ->numeric()
                ->default(0)
                ->helperText('Higher priority rules are applied first'),
            DatePicker::make('starts_at')
                ->label('Starts'),
            DatePicker::make('ends_at')
                ->label('Ends'),
            \Filament\Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('discount_type')
                    ->label('Type')
                    ->colors([
                        'primary',
                    ])
                    ->formatStateUsing(fn (string $state): string => $state === QuoteDiscountType::FIXED->value ? 'Fixed' : 'Percent'),
                TextColumn::make('discount_value')
                    ->label('Value')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('min_quantity')
                    ->label('Min Qty')
                    ->sortable(),
                TextColumn::make('max_quantity')
                    ->label('Max Qty')
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->date(),
                TextColumn::make('ends_at')
                    ->date(),
                BadgeColumn::make('scope')
                    ->colors([
                        'primary',
                        'warning' => 'category',
                        'success' => 'global',
                    ])
                    ->label('Scope'),
                \Filament\Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
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
