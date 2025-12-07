<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeArticleResource\RelationManagers;

use App\Enums\Knowledge\CommentStatus;
use App\Models\KnowledgeArticleComment;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

final class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-chat-bubble-left-ellipsis';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('author_id')
                    ->relationship('author', 'name')
                    ->label(__('app.labels.author'))
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Select::make('status')
                    ->label(__('app.labels.status'))
                    ->options(CommentStatus::class)
                    ->default(CommentStatus::PENDING)
                    ->required(),
                Toggle::make('is_internal')
                    ->label(__('app.labels.internal'))
                    ->default(false),
                Textarea::make('body')
                    ->label(__('app.labels.comment'))
                    ->rows(4)
                    ->required()
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->columns([
                TextColumn::make('body')
                    ->label(__('app.labels.comment'))
                    ->wrap()
                    ->limit(80),
                TextColumn::make('author.name')
                    ->label(__('app.labels.author'))
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->color(fn (CommentStatus|string|null $state): string => $state instanceof CommentStatus ? $state->getColor() : (CommentStatus::tryFrom((string) $state)?->getColor() ?? 'gray'))
                    ->formatStateUsing(fn (CommentStatus|string|null $state): string => $state instanceof CommentStatus ? $state->getLabel() : (CommentStatus::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state))),
                IconColumn::make('is_internal')
                    ->label(__('app.labels.internal'))
                    ->boolean()
                    ->toggleable(),
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
                    Action::make('approve')
                        ->label('Approve')
                        ->visible(fn (KnowledgeArticleComment $record): bool => CommentStatus::tryFrom((string) $record->status) !== CommentStatus::APPROVED)
                        ->action(function (KnowledgeArticleComment $record): void {
                            $record->update(['status' => CommentStatus::APPROVED]);
                        }),
                    Action::make('hide')
                        ->label('Hide')
                        ->color('gray')
                        ->visible(fn (KnowledgeArticleComment $record): bool => CommentStatus::tryFrom((string) $record->status) !== CommentStatus::HIDDEN)
                        ->action(function (KnowledgeArticleComment $record): void {
                            $record->update(['status' => CommentStatus::HIDDEN]);
                        }),
                    DeleteAction::make(),
                ]),
            ]);
    }
}
