<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages\CreateDocument;
use App\Filament\Resources\DocumentResource\Pages\EditDocument;
use App\Filament\Resources\DocumentResource\Pages\ListDocuments;
use App\Filament\Resources\DocumentResource\Pages\ViewDocument;
use App\Filament\Resources\DocumentResource\RelationManagers\SharesRelationManager;
use App\Filament\Resources\DocumentResource\RelationManagers\VersionsRelationManager;
use App\Filament\Support\UploadConstraints;
use App\Models\Document;
use App\Models\User;
use App\Support\Paths\StoragePaths;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

final class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 8;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.workspace');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columns(12)
                    ->schema([
                        Section::make('Document Details')
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->rows(3),
                                Select::make('taxonomyCategories')
                                    ->label(__('app.labels.category'))
                                    ->options(fn () => \App\Models\Taxonomy::query()
                                        ->where('type', 'document_category')
                                        ->orderBy('name')
                                        ->pluck('name', 'id'))
                                    ->relationship('taxonomyCategories')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpanFull(),
                                Select::make('taxonomyTags')
                                    ->label(__('app.labels.tags'))
                                    ->options(fn () => \App\Models\Taxonomy::query()
                                        ->where('type', 'document_tag')
                                        ->orderBy('name')
                                        ->pluck('name', 'id'))
                                    ->relationship('taxonomyTags')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpanFull(),
                                Select::make('template_id')
                                    ->relationship('template', 'name')
                                    ->label('Template')
                                    ->searchable()
                                    ->preload(),
                                Radio::make('visibility')
                                    ->label('Privacy')
                                    ->options([
                                        'private' => 'Private (only you and explicitly shared users)',
                                        'team' => 'Team (anyone on the team)',
                                        'public' => 'Public (shared link)',
                                    ])
                                    ->inline()
                                    ->default('private'),
                                UploadConstraints::apply(
                                    FileUpload::make('upload')
                                        ->label('Initial file')
                                        ->disk('public')
                                        ->directory(fn (): string => StoragePaths::documentsDirectory(self::resolveTeamId()))
                                        ->getUploadedFileNameForStorageUsing(
                                            fn (TemporaryUploadedFile $file): string => \Blaspsoft\Onym\Facades\Onym::make(
                                                defaultFilename: '',
                                                strategy: 'uuid',
                                                extension: $file->getClientOriginalExtension(),
                                                options: ['suffix' => '_' . now()->format('Ymd')],
                                            ),
                                        )
                                        ->helperText('Optional: upload the first version now.')
                                        ->dehydrated(false),
                                    types: ['documents', 'images'],
                                ),
                            ])
                            ->columns(2)
                            ->columnSpan(12),
                        Section::make('Sharing')
                            ->description('Share this document with teammates and set their access level.')
                            ->schema([
                                CheckboxList::make('share_user_ids')
                                    ->label('Share with people')
                                    ->options(function () {
                                        $teamId = Filament::getTenant()?->getKey() ?? Auth::user()?->currentTeam?->getKey();

                                        return User::query()
                                            ->when($teamId, fn (Builder $query, int $team): Builder => $query->whereHas(
                                                'teams',
                                                fn (Builder $builder): Builder => $builder->where('teams.id', $team),
                                            ))
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    })
                                    ->columns(2)
                                    ->searchable()
                                    ->bulkToggleable()
                                    ->default(fn (?Document $record): array => $record?->shares()->pluck('user_id')->all() ?? [])
                                    ->helperText('Selected users receive view access. Use the list below to grant edit permissions.')
                                    ->dehydrated(false),
                                Repeater::make('shares')
                                    ->relationship()
                                    ->label('Specific permissions')
                                    ->schema([
                                        Select::make('user_id')
                                            ->label('User')
                                            ->relationship('user', 'name', modifyQueryUsing: function (Builder $query): Builder {
                                                $teamId = Filament::getTenant()?->getKey() ?? Auth::user()?->currentTeam?->getKey();

                                                return $query->when($teamId, fn (Builder $builder, int $team): Builder => $builder->whereHas(
                                                    'teams',
                                                    fn (Builder $teamQuery): Builder => $teamQuery->where('teams.id', $team),
                                                ));
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                        Select::make('permission')
                                            ->label('Permission')
                                            ->options([
                                                'view' => 'View',
                                                'edit' => 'Edit',
                                            ])
                                            ->default('view')
                                            ->required(),
                                    ])
                                    ->addActionLabel('Add person')
                                    ->columns(2)
                                    ->collapsed()
                                    ->itemLabel(fn (array $state): ?string => isset($state['user_id']) ? User::find($state['user_id'])?->name : null),
                            ])
                            ->columns(1)
                            ->columnSpan(12),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('visibility')
                    ->colors([
                        'gray' => 'private',
                        'info' => 'team',
                        'success' => 'public',
                    ]),
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
                TextColumn::make('template.name')
                    ->label('Template')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('currentVersion.version')
                    ->label('Version')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('taxonomyCategories')
                    ->label(__('app.labels.category'))
                    ->multiple()
                    ->relationship('taxonomyCategories', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('taxonomyTags')
                    ->label(__('app.labels.tags'))
                    ->multiple()
                    ->relationship('taxonomyTags', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            VersionsRelationManager::class,
            SharesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
            'view' => ViewDocument::route('/{record}'),
            'edit' => EditDocument::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<Document>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    private static function resolveTeamId(): ?int
    {
        return Filament::getTenant()?->getKey() ?? Auth::user()?->currentTeam?->getKey();
    }
}
