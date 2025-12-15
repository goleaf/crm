<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Clusters\Settings;
use App\Filament\Resources\RoleResource\Pages;
use App\Models\Role;
use App\Services\Role\RoleManagementService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $cluster = Settings::class;

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.roles');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.role');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.roles');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('app.labels.basic_information'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('app.labels.name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->rules(['alpha_dash']),

                                Forms\Components\TextInput::make('display_name')
                                    ->label(__('app.labels.display_name'))
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label(__('app.labels.description'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('is_template')
                                    ->label(__('app.labels.is_template'))
                                    ->helperText(__('app.helpers.role_template')),

                                Forms\Components\Toggle::make('is_admin_role')
                                    ->label(__('app.labels.is_admin_role'))
                                    ->helperText(__('app.helpers.admin_role')),

                                Forms\Components\Toggle::make('is_studio_role')
                                    ->label(__('app.labels.is_studio_role'))
                                    ->helperText(__('app.helpers.studio_role')),
                            ]),
                    ]),

                Forms\Components\Section::make(__('app.labels.inheritance'))
                    ->schema([
                        Forms\Components\Select::make('parent_role_id')
                            ->label(__('app.labels.parent_role'))
                            ->relationship('parentRole', 'display_name')
                            ->searchable()
                            ->preload()
                            ->helperText(__('app.helpers.role_inheritance')),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make(__('app.labels.permissions'))
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->label(__('app.labels.permissions'))
                            ->relationship('permissions', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record): string|array => str_replace([':', '_'], [' → ', ' '], title_case($record->name)),
                            )
                            ->columns(3)
                            ->searchable()
                            ->bulkToggleable(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make(__('app.labels.metadata'))
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label(__('app.labels.metadata'))
                            ->keyLabel(__('app.labels.key'))
                            ->valueLabel(__('app.labels.value')),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label(__('app.labels.display_name'))
                    ->searchable()
                    ->sortable()
                    ->default(fn ($record) => $record->name),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('app.labels.name'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('app.labels.description'))
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_template')
                    ->label(__('app.labels.template'))
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_admin_role')
                    ->label(__('app.labels.admin'))
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_studio_role')
                    ->label(__('app.labels.studio'))
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('parentRole.display_name')
                    ->label(__('app.labels.parent_role'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('users_count')
                    ->label(__('app.labels.users'))
                    ->counts('users')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label(__('app.labels.permissions'))
                    ->counts('permissions')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_template')
                    ->label(__('app.labels.templates_only')),

                Tables\Filters\TernaryFilter::make('is_admin_role')
                    ->label(__('app.labels.admin_roles_only')),

                Tables\Filters\TernaryFilter::make('is_studio_role')
                    ->label(__('app.labels.studio_roles_only')),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('permissions_matrix')
                    ->label(__('app.actions.view_permissions'))
                    ->icon('heroicon-o-eye')
                    ->modalHeading(__('app.modals.permissions_matrix'))
                    ->modalContent(fn (Role $record): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View => view('filament.modals.role-permissions-matrix', [
                        'role' => $record,
                        'matrix' => resolve(RoleManagementService::class)->getRolePermissionsMatrix($record),
                    ]))
                    ->modalWidth('7xl'),

                Tables\Actions\Action::make('create_from_template')
                    ->label(__('app.actions.create_from_template'))
                    ->icon('heroicon-o-document-duplicate')
                    ->visible(fn (Role $record) => $record->is_template)
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label(__('app.labels.name'))
                            ->required(),
                        Forms\Components\TextInput::make('display_name')
                            ->label(__('app.labels.display_name')),
                    ])
                    ->action(function (Role $record, array $data): void {
                        $service = resolve(RoleManagementService::class);
                        $service->createFromTemplate($record, $data);
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view' => Pages\ViewRole::route('/{record}'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('viewAny', Role::class);
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create', Role::class);
    }

    public static function canView($record): bool
    {
        return auth()->user()->can('view', $record);
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update', $record);
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete', $record);
    }
}
