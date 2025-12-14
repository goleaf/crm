<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ProductLifecycleStage;
use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Support\SlugHelper;
use App\Models\Product;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(SlugHelper::updateSlug()),
                            TextInput::make('slug')
                                ->maxLength(255)
                                ->rules(['nullable', 'slug'])
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
                                ->options(ProductLifecycleStage::class)
                                ->default(ProductLifecycleStage::RELEASED),
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
                                ->visible(fn (Get $get): bool => $get('track_inventory')),
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
                    Select::make('taxonomyCategories')
                        ->label(__('app.labels.categories'))
                        ->relationship('taxonomyCategories', 'name')
                        ->options(fn () => \App\Models\Taxonomy::query()
                            ->where('type', 'product_category')
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->columnSpanFull(),
                ]),
            Section::make('Custom Fields')
                ->schema(function (?Product $record): array {
                    $tenant = Filament::getTenant();
                    if (! $tenant) {
                        return [];
                    }

                    $customFields = \Relaticle\CustomFields\Models\CustomField::query()
                        ->where('model_type', Product::class)
                        ->where('team_id', $tenant->id)
                        ->orderBy('sort_order')
                        ->get();

                    if ($customFields->isEmpty()) {
                        return [
                            \Filament\Forms\Components\Placeholder::make('no_custom_fields')
                                ->label('No custom fields configured')
                                ->content('Custom fields can be configured in the system settings.'),
                        ];
                    }

                    $fields = [];
                    foreach ($customFields as $field) {
                        $component = match ($field->type) {
                            'text' => TextInput::make("custom_field_{$field->id}")
                                ->label($field->name)
                                ->maxLength(255),
                            'textarea' => Textarea::make("custom_field_{$field->id}")
                                ->label($field->name)
                                ->rows(3),
                            'number' => TextInput::make("custom_field_{$field->id}")
                                ->label($field->name)
                                ->numeric(),
                            'email' => TextInput::make("custom_field_{$field->id}")
                                ->label($field->name)
                                ->email(),
                            'url' => TextInput::make("custom_field_{$field->id}")
                                ->label($field->name)
                                ->url(),
                            'select' => Select::make("custom_field_{$field->id}")
                                ->label($field->name)
                                ->options($field->options->pluck('label', 'value')->toArray()),
                            'multi_select' => Select::make("custom_field_{$field->id}")
                                ->label($field->name)
                                ->multiple()
                                ->options($field->options->pluck('label', 'value')->toArray()),
                            'boolean', 'toggle' => \Filament\Forms\Components\Toggle::make("custom_field_{$field->id}")
                                ->label($field->name),
                            'date' => \Filament\Forms\Components\DatePicker::make("custom_field_{$field->id}")
                                ->label($field->name),
                            'datetime' => \Filament\Forms\Components\DateTimePicker::make("custom_field_{$field->id}")
                                ->label($field->name),
                            default => TextInput::make("custom_field_{$field->id}")
                                ->label($field->name)
                        };

                        if ($field->is_required) {
                            $component = $component->required();
                        }

                        if ($field->description) {
                            $component = $component->helperText($field->description);
                        }

                        // Set the value from the custom field relationship
                        if ($record instanceof \App\Models\Product) {
                            $customFieldValue = $record->customFieldValues()
                                ->where('custom_field_id', $field->id)
                                ->first();
                            if ($customFieldValue) {
                                $component = $component->default($customFieldValue->value);
                            }
                        }

                        $fields[] = $component;
                    }

                    return $fields;
                })
                ->collapsible()
                ->collapsed(fn (?Product $record): bool => ! $record instanceof \App\Models\Product), // Collapsed for new records
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                // Include relationships for search and filtering

                fn (Builder $query) => $query->with([
                    'customFieldValues.customField',
                    'taxonomyCategories',
                    'attributeAssignments.attribute',
                    'attributeAssignments.attributeValue',
                ]))
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
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('manufacturer')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('taxonomyCategories.name')
                    ->label(__('app.labels.categories'))
                    ->badge()
                    ->separator(',')
                    ->toggleable(),
                TextColumn::make('price')
                    ->label('List Price')
                    ->money(fn (Product $record): string => $record->currency_code ?? 'USD')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
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
            ->defaultSort('name')
            ->searchable()
            ->globalSearchAttributes(['name', 'sku', 'description', 'manufacturer', 'part_number'])
            ->filters([
                // Category filter with subcategory inclusion
                \Filament\Tables\Filters\SelectFilter::make('taxonomyCategories')
                    ->label(__('app.labels.category'))
                    ->multiple()
                    ->relationship('taxonomyCategories', 'name')
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        return $query->whereHas('taxonomyCategories', function (Builder $subQuery) use ($data): void {
                            $subQuery->whereIn('taxonomies.id', $data['values'])
                                ->orWhereHas('parent', function (Builder $parentQuery) use ($data): void {
                                    $parentQuery->whereIn('id', $data['values']);
                                });
                        });
                    }),

                // Status filter
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label(__('app.labels.status'))
                    ->options([
                        'active' => __('app.labels.active'),
                        'inactive' => __('app.labels.inactive'),
                        'discontinued' => __('app.labels.discontinued'),
                        'draft' => __('app.labels.draft'),
                    ])
                    ->multiple(),

                // Product type filter
                \Filament\Tables\Filters\SelectFilter::make('product_type')
                    ->label(__('app.labels.product_type'))
                    ->options([
                        'stocked' => __('app.labels.stocked'),
                        'service' => __('app.labels.service'),
                        'non_stock' => __('app.labels.non_stock'),
                    ])
                    ->multiple(),

                // Lifecycle stage filter
                \Filament\Tables\Filters\SelectFilter::make('lifecycle_stage')
                    ->label(__('app.labels.lifecycle_stage'))
                    ->options(ProductLifecycleStage::class)
                    ->multiple(),

                // Active/Inactive filter
                \Filament\Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('app.labels.active'))
                    ->boolean()
                    ->trueLabel(__('app.labels.active'))
                    ->falseLabel(__('app.labels.inactive'))
                    ->placeholder(__('app.labels.all')),

                // Inventory tracking filter
                \Filament\Tables\Filters\TernaryFilter::make('track_inventory')
                    ->label(__('app.labels.track_inventory'))
                    ->boolean()
                    ->trueLabel(__('app.labels.tracked'))
                    ->falseLabel(__('app.labels.not_tracked'))
                    ->placeholder(__('app.labels.all')),

                // Bundle filter
                \Filament\Tables\Filters\TernaryFilter::make('is_bundle')
                    ->label(__('app.labels.bundle'))
                    ->boolean()
                    ->trueLabel(__('app.labels.bundle'))
                    ->falseLabel(__('app.labels.single_product'))
                    ->placeholder(__('app.labels.all')),

                // Price range filter
                \Filament\Tables\Filters\Filter::make('price_range')
                    ->label(__('app.labels.price_range'))
                    ->form([
                        \Filament\Forms\Components\Grid::make(2)
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('price_from')
                                    ->label(__('app.labels.price_from'))
                                    ->numeric()
                                    ->prefix('$'),
                                \Filament\Forms\Components\TextInput::make('price_to')
                                    ->label(__('app.labels.price_to'))
                                    ->numeric()
                                    ->prefix('$'),
                            ]),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['price_from'],
                            fn (Builder $query, $price): Builder => $query->where('price', '>=', $price),
                        )
                        ->when(
                            $data['price_to'],
                            fn (Builder $query, $price): Builder => $query->where('price', '<=', $price),
                        )),

                // Inventory level filter
                \Filament\Tables\Filters\Filter::make('inventory_level')
                    ->label(__('app.labels.inventory_level'))
                    ->form([
                        \Filament\Forms\Components\Select::make('inventory_status')
                            ->label(__('app.labels.inventory_status'))
                            ->options([
                                'in_stock' => __('app.labels.in_stock'),
                                'low_stock' => __('app.labels.low_stock'),
                                'out_of_stock' => __('app.labels.out_of_stock'),
                            ]),
                        \Filament\Forms\Components\TextInput::make('low_stock_threshold')
                            ->label(__('app.labels.low_stock_threshold'))
                            ->numeric()
                            ->default(10)
                            ->visible(fn (Get $get): bool => $get('inventory_status') === 'low_stock'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['inventory_status'])) {
                            return $query;
                        }

                        return match ($data['inventory_status']) {
                            'in_stock' => $query->where('inventory_quantity', '>', 0),
                            'out_of_stock' => $query->where('inventory_quantity', '<=', 0),
                            'low_stock' => $query->where('inventory_quantity', '>', 0)
                                ->where('inventory_quantity', '<=', $data['low_stock_threshold'] ?? 10),
                            default => $query,
                        };
                    }),

                // Attribute-based filters (dynamic based on available attributes)
                ...self::getAttributeFilters(),

                // Custom field filters (dynamic based on available custom fields)
                ...self::getCustomFieldFilters(),
            ])
            ->persistFiltersInSession()
            ->filtersFormColumns(3);
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
                fn (Builder $query): Builder => $query->whereBelongsTo($tenant, 'team'),
            );
    }

    /**
     * Get dynamic attribute-based filters
     */
    private static function getAttributeFilters(): array
    {
        $tenant = Filament::getTenant();
        if (! $tenant) {
            return [];
        }

        $attributes = \App\Models\ProductAttribute::query()
            ->where('team_id')
            ->where('is_filterable')
            ->with('values')
            ->get();

        $filters = [];

        foreach ($attributes as $attribute) {
            $filterId = "attribute_{$attribute->id}";

            if ($attribute->data_type === 'select' || $attribute->data_type === 'multi_select') {
                $filters[] = \Filament\Tables\Filters\SelectFilter::make($filterId)
                    ->label($attribute->name)
                    ->multiple($attribute->data_type === 'multi_select')
                    ->options($attribute->values->pluck('value', 'id')->toArray())
                    ->query(function (Builder $query, array $data) use ($attribute): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        return $query->whereHas('attributeAssignments', function (Builder $subQuery) use ($attribute, $data): void {
                            $subQuery->where('product_attribute_id', $attribute->id)
                                ->whereIn('product_attribute_value_id', $data['values']);
                        });
                    });
            } elseif ($attribute->data_type === 'boolean') {
                $filters[] = \Filament\Tables\Filters\TernaryFilter::make($filterId)
                    ->label($attribute->name)
                    ->boolean()
                    ->query(function (Builder $query, ?string $state) use ($attribute): Builder {
                        if ($state === null) {
                            return $query;
                        }

                        $boolValue = $state === '1';

                        return $query->whereHas('attributeAssignments', function (Builder $subQuery) use ($attribute, $boolValue): void {
                            $subQuery->where('product_attribute_id', $attribute->id)
                                ->where('value', $boolValue ? 'true' : 'false');
                        });
                    });
            } elseif ($attribute->data_type === 'number') {
                $filters[] = \Filament\Tables\Filters\Filter::make($filterId)
                    ->label($attribute->name)
                    ->form([
                        \Filament\Forms\Components\Grid::make(2)
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('min_value')
                                    ->label(__('app.labels.minimum'))
                                    ->numeric(),
                                \Filament\Forms\Components\TextInput::make('max_value')
                                    ->label(__('app.labels.maximum'))
                                    ->numeric(),
                            ]),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->whereHas('attributeAssignments', function (Builder $subQuery) use ($attribute, $data): void {
                        $subQuery->where('product_attribute_id', $attribute->id);

                        if (! empty($data['min_value'])) {
                            $subQuery->where('value', '>=', $data['min_value']);
                        }

                        if (! empty($data['max_value'])) {
                            $subQuery->where('value', '<=', $data['max_value']);
                        }
                    }));
            }
        }

        return $filters;
    }

    /**
     * Get dynamic custom field filters
     */
    private static function getCustomFieldFilters(): array
    {
        $tenant = Filament::getTenant();
        if (! $tenant) {
            return [];
        }

        $customFields = \Relaticle\CustomFields\Models\CustomField::query()
            ->where('model_type', Product::class)
            ->where('team_id', $tenant->id)
            ->get();

        $filters = [];

        foreach ($customFields as $field) {
            $filterId = "custom_field_{$field->id}";

            if ($field->type === 'select' || $field->type === 'multi_select') {
                $options = $field->options->pluck('label', 'value')->toArray();

                $filters[] = \Filament\Tables\Filters\SelectFilter::make($filterId)
                    ->label($field->name)
                    ->multiple($field->type === 'multi_select')
                    ->options($options)
                    ->query(function (Builder $query, array $data) use ($field): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        return $query->whereHas('customFieldValues', function (Builder $subQuery) use ($field, $data): void {
                            $subQuery->where('custom_field_id', $field->id);

                            if ($field->type === 'multi_select') {
                                // For multi-select, check if any of the selected values are in the JSON array
                                foreach ($data['values'] as $value) {
                                    $subQuery->orWhereJsonContains('value', $value);
                                }
                            } else {
                                $subQuery->whereIn('value', $data['values']);
                            }
                        });
                    });
            } elseif ($field->type === 'boolean' || $field->type === 'toggle') {
                $filters[] = \Filament\Tables\Filters\TernaryFilter::make($filterId)
                    ->label($field->name)
                    ->boolean()
                    ->query(function (Builder $query, ?string $state) use ($field): Builder {
                        if ($state === null) {
                            return $query;
                        }

                        $boolValue = $state === '1';

                        return $query->whereHas('customFieldValues', function (Builder $subQuery) use ($field, $boolValue): void {
                            $subQuery->where('custom_field_id', $field->id)
                                ->where('value', $boolValue);
                        });
                    });
            }
        }

        return $filters;
    }

    /**
     * Enhanced global search that includes custom fields and categories
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with([
            'customFieldValues.customField',
            'taxonomyCategories',
        ]);
    }

    /**
     * Get global search results with additional details
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        if ($record->sku) {
            $details['SKU'] = $record->sku;
        }

        if ($record->manufacturer) {
            $details['Manufacturer'] = $record->manufacturer;
        }

        if ($record->taxonomyCategories->isNotEmpty()) {
            $details['Categories'] = $record->taxonomyCategories->pluck('name')->join(', ');
        }

        if ($record->price) {
            $details['Price'] = money($record->price, $record->currency_code ?? 'USD');
        }

        return $details;
    }

    /**
     * Enhanced global search query that searches across all relevant fields
     */
    public static function getGlobalSearchQuery(string $search): Builder
    {
        return self::getEloquentQuery()
            ->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('manufacturer', 'like', "%{$search}%")
                    ->orWhere('part_number', 'like', "%{$search}%")
                    // Search in categories
                    ->orWhereHas('taxonomyCategories', function (Builder $categoryQuery) use ($search): void {
                        $categoryQuery->where('name', 'like', "%{$search}%");
                    })
                    // Search in custom fields
                    ->orWhereHas('customFieldValues', function (Builder $customFieldQuery) use ($search): void {
                        $customFieldQuery->where('value', 'like', "%{$search}%");
                    })
                    // Search in attribute values
                    ->orWhereHas('attributeAssignments', function (Builder $attributeQuery) use ($search): void {
                        $attributeQuery->where('value', 'like', "%{$search}%")
                            ->orWhereHas('attributeValue', function (Builder $valueQuery) use ($search): void {
                                $valueQuery->where('value', 'like', "%{$search}%");
                            });
                    });
            });
    }
}
