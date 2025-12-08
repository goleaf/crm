<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\Knowledge\ArticleVisibility;
use App\Filament\Resources\KnowledgeCategoryResource\Pages\CreateKnowledgeCategory;
use App\Filament\Resources\KnowledgeCategoryResource\Pages\EditKnowledgeCategory;
use App\Filament\Resources\KnowledgeCategoryResource\Pages\ListKnowledgeCategories;
use App\Filament\Support\SlugHelper;
use App\Models\KnowledgeCategory;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

final class KnowledgeCategoryResource extends Resource
{
    protected static ?string $model = KnowledgeCategory::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 21;

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.knowledge_base');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.labels.categories');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category Details')
                    ->schema([
                        TextInput::make('name')
                            ->label(__('app.labels.name'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(SlugHelper::updateSlug()),
                        TextInput::make('slug')
                            ->label(__('app.labels.slug'))
                            ->rules(['nullable', 'slug'])
                            ->maxLength(255)
                            ->helperText('Generated from the name if left blank.'),
                        Select::make('parent_id')
                            ->relationship('parent', 'name')
                            ->label(__('app.labels.parent_category'))
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Select::make('visibility')
                            ->label(__('app.labels.visibility'))
                            ->options(ArticleVisibility::class)
                            ->default(ArticleVisibility::INTERNAL)
                            ->required(),
                        TextInput::make('position')
                            ->label(__('app.labels.position'))
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_active')
                            ->label(__('app.labels.active'))
                            ->default(true),
                        Textarea::make('description')
                            ->label(__('app.labels.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.labels.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('visibility')
                    ->label(__('app.labels.visibility'))
                    ->badge()
                    ->formatStateUsing(fn (ArticleVisibility|string|null $state): string => $state instanceof ArticleVisibility ? $state->getLabel() : (ArticleVisibility::tryFrom((string) $state)?->getLabel() ?? Str::headline((string) $state)))
                    ->color(fn (ArticleVisibility|string|null $state): string => $state instanceof ArticleVisibility ? $state->getColor() : (ArticleVisibility::tryFrom((string) $state)?->getColor() ?? 'gray')),
                TextColumn::make('parent.name')
                    ->label(__('app.labels.parent_category'))
                    ->toggleable(),
                TextColumn::make('position')
                    ->label(__('app.labels.position'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('app.labels.active'))
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('app.labels.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('position')
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
            'index' => ListKnowledgeCategories::route('/'),
            'create' => CreateKnowledgeCategory::route('/create'),
            'edit' => EditKnowledgeCategory::route('/{record}/edit'),
        ];
    }
}
