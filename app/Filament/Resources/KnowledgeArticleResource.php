<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\Knowledge\ArticleStatus;
use App\Enums\Knowledge\ArticleVisibility;
use App\Filament\Resources\KnowledgeArticleResource\Forms\KnowledgeArticleForm;
use App\Filament\Resources\KnowledgeArticleResource\Pages\CreateKnowledgeArticle;
use App\Filament\Resources\KnowledgeArticleResource\Pages\EditKnowledgeArticle;
use App\Filament\Resources\KnowledgeArticleResource\Pages\ListKnowledgeArticles;
use App\Filament\Resources\KnowledgeArticleResource\Pages\ViewKnowledgeArticle;
use App\Filament\Resources\KnowledgeArticleResource\RelationManagers\ApprovalsRelationManager;
use App\Filament\Resources\KnowledgeArticleResource\RelationManagers\CommentsRelationManager;
use App\Filament\Resources\KnowledgeArticleResource\RelationManagers\RatingsRelationManager;
use App\Filament\Resources\KnowledgeArticleResource\RelationManagers\RelatedArticlesRelationManager;
use App\Filament\Resources\KnowledgeArticleResource\RelationManagers\VersionsRelationManager;
use App\Models\KnowledgeArticle;
use App\Support\Reactions\ReactionOptions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Override;

final class KnowledgeArticleResource extends Resource
{
    protected static ?string $model = KnowledgeArticle::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?int $navigationSort = 20;

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.knowledge_base');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.labels.articles');
    }

    public static function form(Schema $schema): Schema
    {
        return KnowledgeArticleForm::get($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query
                    ->withAvg('ratings', 'rating')
                    ->withCount('reactions')
                    ->when(
                        auth()->check(),
                        fn (Builder $builder): Builder => $builder->withExists([
                            'reactions as reacted_by_me' => fn (Builder $reactionQuery): Builder => $reactionQuery->where(
                                config('laravel-reactions.user.foreign_key', 'user_id'),
                                auth()->id()
                            ),
                        ])
                    )
            )
            ->columns([
                TextColumn::make('title')
                    ->label(__('app.labels.title'))
                    ->searchable()
                    ->wrap()
                    ->limit(60),
                TextColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->color(fn (ArticleStatus|string|null $state): string => $state instanceof ArticleStatus ? $state->getColor() : (ArticleStatus::tryFrom((string) $state)?->getColor() ?? 'gray'))
                    ->formatStateUsing(fn (ArticleStatus|string|null $state): string => $state instanceof ArticleStatus ? $state->getLabel() : (ArticleStatus::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state)))
                    ->sortable(),
                TextColumn::make('visibility')
                    ->label(__('app.labels.visibility'))
                    ->badge()
                    ->color(fn (ArticleVisibility|string|null $state): string => $state instanceof ArticleVisibility ? $state->getColor() : (ArticleVisibility::tryFrom((string) $state)?->getColor() ?? 'gray'))
                    ->formatStateUsing(fn (ArticleVisibility|string|null $state): string => $state instanceof ArticleVisibility ? $state->getLabel() : (ArticleVisibility::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state))),
                TextColumn::make('taxonomyCategories.name')
                    ->label(__('app.labels.category'))
                    ->badge()
                    ->separator(',')
                    ->toggleable(),
                TextColumn::make('taxonomyTags.name')
                    ->label(__('app.labels.tags'))
                    ->badge()
                    ->separator(',')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('author.name')
                    ->label(__('app.labels.author'))
                    ->toggleable(),
                TextColumn::make('ratings_avg')
                    ->label(__('app.labels.rating'))
                    ->state(fn (KnowledgeArticle $record): string => number_format((float) ($record->ratings_avg_rating ?? $record->ratings()->avg('rating')), 1))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('ratings_avg_rating', $direction))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('view_count')
                    ->label(__('app.labels.views'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('reactions_count')
                    ->label(__('app.labels.reactions'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('reacted_by_me')
                    ->label(__('app.labels.my_reaction'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('published_at')
                    ->label(__('app.labels.published_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_featured')
                    ->label(__('app.labels.featured'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('app.labels.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('app.labels.status'))
                    ->options(ArticleStatus::class)
                    ->multiple(),
                SelectFilter::make('visibility')
                    ->label(__('app.labels.visibility'))
                    ->options(ArticleVisibility::class)
                    ->multiple(),
                SelectFilter::make('taxonomyCategories')
                    ->label(__('app.labels.category'))
                    ->multiple()
                    ->relationship('taxonomyCategories', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('taxonomyTags')
                    ->label(__('app.labels.tags'))
                    ->multiple()
                    ->relationship('taxonomyTags', 'name')
                    ->searchable()
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('react')
                        ->label(__('app.actions.react'))
                        ->icon('heroicon-o-hand-thumb-up')
                        ->form([
                            Select::make('type')
                                ->label(__('app.labels.reaction_type'))
                                ->options(ReactionOptions::options())
                                ->default(ReactionOptions::default())
                                ->required(),
                        ])
                        ->action(function (KnowledgeArticle $record, array $data): void {
                            $user = auth()->user();

                            if ($user === null) {
                                return;
                            }

                            $user->reaction($data['type'], $record);
                        }),
                    Action::make('removeReaction')
                        ->label(__('app.actions.remove_reaction'))
                        ->color('gray')
                        ->visible(fn (): bool => auth()->check())
                        ->action(function (KnowledgeArticle $record): void {
                            $user = auth()->user();

                            if ($user === null) {
                                return;
                            }

                            $user->removeReactions($record);
                        }),
                    RestoreAction::make(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                //
            ]);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            ApprovalsRelationManager::class,
            CommentsRelationManager::class,
            RatingsRelationManager::class,
            RelatedArticlesRelationManager::class,
            VersionsRelationManager::class,
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListKnowledgeArticles::route('/'),
            'create' => CreateKnowledgeArticle::route('/create'),
            'view' => ViewKnowledgeArticle::route('/{record}'),
            'edit' => EditKnowledgeArticle::route('/{record}/edit'),
        ];
    }
}
