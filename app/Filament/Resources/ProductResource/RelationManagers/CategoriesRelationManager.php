<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Filament\Support\SlugHelper;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                        $data['team_id'] = $this->ownerRecord->team_id;

                        return $data;
                    }),
                AttachAction::make()
                    ->recordSelectOptionsQuery(fn (Builder $query): Builder => $query
                        ->where('type', 'product_category')
                        ->where('team_id', $this->ownerRecord->team_id)
                        ->orderBy('name')),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make(),
            ]);
    }
}
