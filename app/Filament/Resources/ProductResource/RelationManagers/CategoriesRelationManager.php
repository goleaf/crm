<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Filament\Support\SlugHelper;
use App\Models\Taxonomy;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DetachAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class CategoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'taxonomyCategories';

    protected static ?string $title = 'Categories';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(SlugHelper::updateSlug()),
            TextInput::make('slug')
                ->rules(['nullable', 'slug'])
                ->maxLength(255),
            Textarea::make('description')
                ->maxLength(1000)
                ->rows(3),
            TextInput::make('type')
                ->default('product_category')
                ->dehydrated()
                ->hidden(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('description')
                    ->limit(50),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['type'] = 'product_category';

                        return $data;
                    }),
                AttachAction::make()
                    ->recordSelectOptions(fn () => Taxonomy::query()
                        ->where('type', 'product_category')
                        ->orderBy('name')
                        ->pluck('name', 'id')),
            ])
            ->actions([
                EditAction::make(),
                DetachAction::make(),
            ]);
    }
}
