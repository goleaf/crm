<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\Product;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Basic Information')
                ->schema([
                    Grid::make()
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('slug')
                                ->maxLength(255)
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('Auto-generated from name'),
                        ]),
                    Grid::make()
                        ->columns(3)
                        ->schema([
                            TextInput::make('sku')
                                ->label('SKU')
                                ->maxLength(120)
                                ->unique(ignoreRecord: true),
                            TextInput::make('part_number')
                                ->label('Part Number')
                                ->maxLength(120)
                                ->unique(ignoreRecord: true),
                            TextInput::make('manufacturer')
                                ->maxLength(255),
                        ]),
                    Grid::make()
                        ->columns(3)
                        ->schema([
                            Select::make('product_type')
                                ->label('Type')
                                ->options([
                                    'stocked' => 'Stocked',
                                    'service' => 'Service',
                                    'non_stock' => 'Non-stock',
                                ])
                                ->default('stocked'),
                            Select::make('status')
                                ->options([
                                    'active' => 'Active',
                                    'inactive' => 'Inactive',
                                ])
                                ->default('active'),
                            Select::make('lifecycle_stage')
                                ->label('Lifecycle')
                                ->options([
                                    'draft' => 'Draft',
                                    'released' => 'Released',
                                    'end_of_life' => 'End of Life',
                                ])
                                ->default('released'),
                        ]),
                ])
                ->columns(1),
            Section::make('Pricing & Inventory')
                ->schema([
                    Grid::make()
                        ->columns(3)
                        ->schema([
                            TextInput::make('price')
                                ->label('List Price')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->prefix('$'),
                            TextInput::make('cost_price')
                                ->label('Cost')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('$'),
                            TextInput::make('currency_code')
                                ->label('Currency')
                                ->maxLength(3)
                                ->default('USD'),
                        ]),
                    Grid::make()
                        ->columns(3)
                        ->schema([
                            \Filament\Forms\Components\DatePicker::make('price_effective_from')
                                ->label('Price Starts'),
                            \Filament\Forms\Components\DatePicker::make('price_effective_to')
                                ->label('Price Ends'),
                            \Filament\Forms\Components\Toggle::make('is_active')
                                ->label('Active')
                                ->default(true),
                        ]),
                    Grid::make()
                        ->columns(3)
                        ->schema([
                            \Filament\Forms\Components\Toggle::make('track_inventory')
                                ->label('Track Inventory')
                                ->reactive()
                                ->default(false),
                            TextInput::make('inventory_quantity')
                                ->label('Inventory Quantity')
                                ->numeric()
                                ->default(0)
                                ->visible(fn ($get) => $get('track_inventory')),
                            \Filament\Forms\Components\Toggle::make('is_bundle')
                                ->label('Bundle Product'),
                        ]),
                ])
                ->columns(1),
            Section::make('Description')
                ->schema([
                    Textarea::make('description')
                        ->maxLength(2000)
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
            Section::make('Categories')
                ->schema([
                    Select::make('categories')
                        ->label('Categories')
                        ->relationship('categories', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->columnSpanFull(),
                ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product_type')
                    ->label('Type')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('manufacturer')
                    ->toggleable(),
                TextColumn::make('categories.name')
                    ->label('Categories')
                    ->badge()
                    ->separator(',')
                    ->toggleable(),
                TextColumn::make('price')
                    ->label('List Price')
                    ->money(fn (Product $record): string => $record->currency_code ?? 'USD')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('lifecycle_stage')
                    ->label('Lifecycle')
                    ->badge()
                    ->toggleable(),
                \Filament\Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('inventory_quantity')
                    ->label('Stock')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('variations_count')
                    ->label('Variations')
                    ->counts('variations')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            ProductResource\RelationManagers\CategoriesRelationManager::class,
            ProductResource\RelationManagers\VariationsRelationManager::class,
            ProductResource\RelationManagers\AttributeAssignmentsRelationManager::class,
            ProductResource\RelationManagers\PriceTiersRelationManager::class,
            ProductResource\RelationManagers\DiscountRulesRelationManager::class,
            ProductResource\RelationManagers\RelationshipsRelationManager::class,
            ActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'view' => ProductResource\Pages\ViewProduct::route('/{record}'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<Product>
     */
    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();

        return parent::getEloquentQuery()
            ->when(
                $tenant,
                fn (Builder $query): Builder => $query->whereBelongsTo($tenant, 'team')
            );
    }
}
