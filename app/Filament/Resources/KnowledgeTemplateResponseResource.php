<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\Knowledge\ArticleVisibility;
use App\Filament\Resources\KnowledgeTemplateResponseResource\Pages\CreateKnowledgeTemplateResponse;
use App\Filament\Resources\KnowledgeTemplateResponseResource\Pages\EditKnowledgeTemplateResponse;
use App\Filament\Resources\KnowledgeTemplateResponseResource\Pages\ListKnowledgeTemplateResponses;
use App\Models\KnowledgeTemplateResponse;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Override;

final class KnowledgeTemplateResponseResource extends Resource
{
    protected static ?string $model = KnowledgeTemplateResponse::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static ?int $navigationSort = 24;

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.knowledge_base');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.labels.template_responses');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template')
                    ->schema([
                        TextInput::make('title')
                            ->label(__('app.labels.title'))
                            ->required()
                            ->maxLength(255),
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->label(__('app.labels.category'))
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Select::make('visibility')
                            ->label(__('app.labels.visibility'))
                            ->options(ArticleVisibility::class)
                            ->default(ArticleVisibility::INTERNAL)
                            ->required(),
                        Toggle::make('is_active')
                            ->label(__('app.labels.active'))
                            ->default(true),
                        RichEditor::make('body')
                            ->label(__('app.labels.body'))
                            ->disableToolbarButtons(['attachFiles'])
                            ->columnSpanFull()
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('app.labels.title'))
                    ->searchable()
                    ->wrap(),
                TextColumn::make('category.name')
                    ->label(__('app.labels.category'))
                    ->toggleable(),
                TextColumn::make('visibility')
                    ->label(__('app.labels.visibility'))
                    ->badge()
                    ->formatStateUsing(fn (ArticleVisibility|string|null $state): string => $state instanceof ArticleVisibility ? $state->getLabel() : (ArticleVisibility::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state)))
                    ->color(fn (ArticleVisibility|string|null $state): string => $state instanceof ArticleVisibility ? $state->getColor() : (ArticleVisibility::tryFrom((string) $state)?->getColor() ?? 'gray')),
                IconColumn::make('is_active')
                    ->label(__('app.labels.active'))
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label(__('app.labels.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
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
            'index' => ListKnowledgeTemplateResponses::route('/'),
            'create' => CreateKnowledgeTemplateResponse::route('/create'),
            'edit' => EditKnowledgeTemplateResponse::route('/{record}/edit'),
        ];
    }
}
