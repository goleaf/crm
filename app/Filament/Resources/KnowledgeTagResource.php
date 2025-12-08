<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\KnowledgeTagResource\Pages\CreateKnowledgeTag;
use App\Filament\Resources\KnowledgeTagResource\Pages\EditKnowledgeTag;
use App\Filament\Resources\KnowledgeTagResource\Pages\ListKnowledgeTags;
use App\Filament\Support\SlugHelper;
use App\Models\KnowledgeTag;
use App\Support\Helpers\StringHelper;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Override;

final class KnowledgeTagResource extends Resource
{
    protected static ?string $model = KnowledgeTag::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 22;

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.knowledge_base');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.labels.tags');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tag Details')
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
                            ->helperText('Generated from the tag name if left blank.'),
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
                TextColumn::make('description')
                    ->label(__('app.labels.description'))
                    ->formatStateUsing(
                        fn (?string $state): HtmlString|string|null => StringHelper::wordWrap(
                            value: $state,
                            characters: 60,
                            break: '<br>',
                            cutLongWords: true,
                        ),
                    )
                    ->html()
                    ->lineClamp(3)
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('app.labels.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
            'index' => ListKnowledgeTags::route('/'),
            'create' => CreateKnowledgeTag::route('/create'),
            'edit' => EditKnowledgeTag::route('/{record}/edit'),
        ];
    }
}
