<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\Knowledge\ArticleVisibility;
use App\Enums\Knowledge\FaqStatus;
use App\Filament\Resources\KnowledgeFaqResource\Pages\CreateKnowledgeFaq;
use App\Filament\Resources\KnowledgeFaqResource\Pages\EditKnowledgeFaq;
use App\Filament\Resources\KnowledgeFaqResource\Pages\ListKnowledgeFaqs;
use App\Models\KnowledgeFaq;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Override;

final class KnowledgeFaqResource extends Resource
{
    protected static ?string $model = KnowledgeFaq::class;

    protected static ?string $recordTitleAttribute = 'question';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?int $navigationSort = 23;

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.knowledge_base');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.labels.faqs');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('FAQ')
                    ->schema([
                        TextInput::make('question')
                            ->label(__('app.labels.question'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('answer')
                            ->label(__('app.labels.answer'))
                            ->rows(4)
                            ->required()
                            ->columnSpanFull(),
                        Select::make('article_id')
                            ->relationship('article', 'title')
                            ->label(__('app.labels.article'))
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->columnSpan(2),
                        Select::make('status')
                            ->label(__('app.labels.status'))
                            ->options(FaqStatus::class)
                            ->default(FaqStatus::PUBLISHED)
                            ->required(),
                        Select::make('visibility')
                            ->label(__('app.labels.visibility'))
                            ->options(ArticleVisibility::class)
                            ->default(ArticleVisibility::PUBLIC)
                            ->required(),
                        TextInput::make('position')
                            ->label(__('app.labels.position'))
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question')
                    ->label(__('app.labels.question'))
                    ->wrap()
                    ->limit(80)
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->color(fn (FaqStatus|string|null $state): string => $state instanceof FaqStatus ? $state->getColor() : (FaqStatus::tryFrom((string) $state)?->getColor() ?? 'gray'))
                    ->formatStateUsing(fn (FaqStatus|string|null $state): string => $state instanceof FaqStatus ? $state->getLabel() : (FaqStatus::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state))),
                TextColumn::make('visibility')
                    ->label(__('app.labels.visibility'))
                    ->badge()
                    ->color(fn (ArticleVisibility|string|null $state): string => $state instanceof ArticleVisibility ? $state->getColor() : (ArticleVisibility::tryFrom((string) $state)?->getColor() ?? 'gray'))
                    ->formatStateUsing(fn (ArticleVisibility|string|null $state): string => $state instanceof ArticleVisibility ? $state->getLabel() : (ArticleVisibility::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state))),
                TextColumn::make('article.title')
                    ->label(__('app.labels.article'))
                    ->toggleable(),
                TextColumn::make('position')
                    ->label(__('app.labels.position'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('app.labels.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('position')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('app.labels.status'))
                    ->options(FaqStatus::class)
                    ->multiple(),
                SelectFilter::make('visibility')
                    ->label(__('app.labels.visibility'))
                    ->options(ArticleVisibility::class)
                    ->multiple(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    RestoreAction::make(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ]);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListKnowledgeFaqs::route('/'),
            'create' => CreateKnowledgeFaq::route('/create'),
            'edit' => EditKnowledgeFaq::route('/{record}/edit'),
        ];
    }
}
