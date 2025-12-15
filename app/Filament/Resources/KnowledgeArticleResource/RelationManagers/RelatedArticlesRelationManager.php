<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeArticleResource\RelationManagers;

use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class RelatedArticlesRelationManager extends RelationManager
{
    protected static string $relationship = 'articleRelations';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-link';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Relation')
                    ->schema([
                        Select::make('related_article_id')
                            ->label(__('app.labels.related_article'))
                            ->options(fn (): array => $this->getOwnerRecord()
                                ->newQuery()
                                ->where('team_id', $this->getOwnerRecord()->team_id)
                                ->whereKeyNot($this->getOwnerRecord()->getKey())
                                ->orderBy('title')
                                ->pluck('title', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('relation_type')
                            ->label(__('app.labels.relation_type'))
                            ->maxLength(64)
                            ->default('related'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('relatedArticle.title')
                    ->label(__('app.labels.article'))
                    ->wrap()
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('relation_type')
                    ->label(__('app.labels.relation_type'))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-plus')->size(Size::Small),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }

    private function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = $this->ownerRecord->team_id;

        return $data;
    }
}
