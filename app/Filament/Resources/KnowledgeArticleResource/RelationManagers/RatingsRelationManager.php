<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeArticleResource\RelationManagers;

use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class RatingsRelationManager extends RelationManager
{
    protected static string $relationship = 'ratings';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-star';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('rating')
                    ->label(__('app.labels.rating'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label(__('app.labels.user'))
                    ->searchable()
                    ->preload()
                    ->nullable(),
                TextInput::make('context')
                    ->label(__('app.labels.source'))
                    ->maxLength(64),
                TextInput::make('ip_address')
                    ->label(__('app.labels.ip_address'))
                    ->maxLength(45),
                Textarea::make('feedback')
                    ->label(__('app.labels.feedback'))
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('rating')
            ->columns([
                TextColumn::make('rating')
                    ->label(__('app.labels.rating'))
                    ->badge()
                    ->color(fn (int|string|null $state): string => (int) $state >= 4 ? 'success' : 'warning')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('app.labels.user'))
                    ->placeholder('Guest')
                    ->toggleable(),
                TextColumn::make('context')
                    ->label(__('app.labels.source'))
                    ->toggleable(),
                TextColumn::make('feedback')
                    ->label(__('app.labels.feedback'))
                    ->wrap()
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
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
}
