<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeArticleResource\RelationManagers;

use App\Enums\Knowledge\ArticleStatus;
use App\Enums\Knowledge\ArticleVisibility;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

final class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Versions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Version')
                    ->schema([
                        TextInput::make('version')
                            ->label(__('app.labels.version'))
                            ->disabled(),
                        TextInput::make('title')
                            ->label(__('app.labels.title'))
                            ->disabled()
                            ->columnSpan(2),
                        TextInput::make('status')
                            ->label(__('app.labels.status'))
                            ->formatStateUsing(fn (ArticleStatus|string|null $state): string => $state instanceof ArticleStatus ? $state->getLabel() : (ArticleStatus::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state)))
                            ->disabled(),
                        TextInput::make('visibility')
                            ->label(__('app.labels.visibility'))
                            ->formatStateUsing(fn (ArticleVisibility|string|null $state): string => $state instanceof ArticleVisibility ? $state->getLabel() : (ArticleVisibility::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state)))
                            ->disabled(),
                        TextInput::make('published_at')
                            ->label(__('app.labels.published_at'))
                            ->disabled(),
                        Textarea::make('summary')
                            ->label(__('app.labels.summary'))
                            ->rows(3)
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version')
            ->columns([
                TextColumn::make('version')
                    ->label(__('app.labels.version'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->color(fn (ArticleStatus|string|null $state): string => $state instanceof ArticleStatus ? $state->getColor() : (ArticleStatus::tryFrom((string) $state)?->getColor() ?? 'gray'))
                    ->formatStateUsing(fn (ArticleStatus|string|null $state): string => $state instanceof ArticleStatus ? $state->getLabel() : (ArticleStatus::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state))),
                TextColumn::make('visibility')
                    ->label(__('app.labels.visibility'))
                    ->badge()
                    ->color(fn (ArticleVisibility|string|null $state): string => $state instanceof ArticleVisibility ? $state->getColor() : (ArticleVisibility::tryFrom((string) $state)?->getColor() ?? 'gray'))
                    ->formatStateUsing(fn (ArticleVisibility|string|null $state): string => $state instanceof ArticleVisibility ? $state->getLabel() : (ArticleVisibility::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state))),
                TextColumn::make('editor.name')
                    ->label(__('app.labels.editor'))
                    ->toggleable(),
                TextColumn::make('approver.name')
                    ->label(__('app.labels.approver'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('published_at')
                    ->label(__('app.labels.published_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('version', 'desc')
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
