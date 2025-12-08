<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProductCategoryResource\Pages\CreateProductCategory;
use App\Filament\Resources\ProductCategoryResource\Pages\EditProductCategory;
use App\Filament\Resources\ProductCategoryResource\Pages\ListProductCategories;
use App\Filament\Support\SlugHelper;
use App\Models\ProductCategory;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ProductCategoryResource extends Resource
{
    protected static ?string $model = ProductCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(SlugHelper::updateSlug()),
            TextInput::make('slug')
                ->maxLength(255)
                ->rules(['nullable', 'slug'])
                ->helperText('Auto-generated from the name if left blank'),
            Select::make('parent_id')
                ->label('Parent Category')
                ->relationship('parent', 'name')
                ->searchable()
                ->preload()
                ->nullable(),
            Textarea::make('description')
                ->maxLength(1000)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('breadcrumb')
                    ->label('Path')
                    ->badge()
                    ->separator(' / ')
                    ->toggleable(),
                TextColumn::make('slug')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('description')
                    ->limit(60)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductCategories::route('/'),
            'create' => CreateProductCategory::route('/create'),
            'edit' => EditProductCategory::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<ProductCategory>
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
