<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SecurityGroupResource\Pages;
use App\Filament\Resources\SecurityGroupResource\RelationManagers\AuditLogsRelationManager;
use App\Filament\Resources\SecurityGroupResource\RelationManagers\BroadcastMessagesRelationManager;
use App\Filament\Resources\SecurityGroupResource\RelationManagers\MembersRelationManager;
use App\Filament\Resources\SecurityGroupResource\RelationManagers\RecordAccessRelationManager;
use App\Models\SecurityGroup;
use App\Services\SecurityGroup\SecurityGroupService;
use BackedEnum;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

final class SecurityGroupResource extends Resource
{
    protected static ?string $model = SecurityGroup::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 160;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.security');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.security_group');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.security_groups');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make(__('app.labels.basic_information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('app.labels.name'))
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: function (Rule $rule): Rule {
                                    $teamId = auth()->user()?->currentTeam?->getKey() ?? auth()->user()?->current_team_id;

                                    return $teamId === null
                                        ? $rule
                                        : $rule->where(fn ($query) => $query->where('team_id', $teamId));
                                },
                            ),
                        Forms\Components\Textarea::make('description')
                            ->label(__('app.labels.description'))
                            ->rows(3)
                            ->maxLength(65535),
                        Forms\Components\Select::make('parent_id')
                            ->label(__('app.labels.parent_group'))
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText(__('app.helpers.parent_group_hierarchy')),
                    ]),

                Forms\Components\Section::make(__('app.labels.security_settings'))
                    ->schema([
                        Forms\Components\Toggle::make('is_security_group')
                            ->label(__('app.labels.is_security_group'))
                            ->default(true)
                            ->helperText(__('app.helpers.security_group_enabled')),
                        Forms\Components\Toggle::make('inherit_permissions')
                            ->label(__('app.labels.inherit_permissions'))
                            ->default(true)
                            ->helperText(__('app.helpers.inherit_from_parent')),
                        Forms\Components\Toggle::make('owner_only_access')
                            ->label(__('app.labels.owner_only_access'))
                            ->helperText(__('app.helpers.owner_only_access')),
                        Forms\Components\Toggle::make('group_only_access')
                            ->label(__('app.labels.group_only_access'))
                            ->helperText(__('app.helpers.group_only_access')),
                        Forms\Components\Toggle::make('is_primary_group')
                            ->label(__('app.labels.is_primary_group'))
                            ->helperText(__('app.helpers.primary_group_designation')),
                        Forms\Components\Toggle::make('active')
                            ->label(__('app.labels.active'))
                            ->default(true),
                    ]),

                Forms\Components\Section::make(__('app.labels.permissions_and_access'))
                    ->schema([
                        Forms\Components\KeyValue::make('record_level_permissions')
                            ->label(__('app.labels.record_level_permissions'))
                            ->keyLabel(__('app.labels.module'))
                            ->valueLabel(__('app.labels.permission'))
                            ->helperText(__('app.helpers.record_level_permissions')),
                        Forms\Components\KeyValue::make('field_level_permissions')
                            ->label(__('app.labels.field_level_permissions'))
                            ->keyLabel(__('app.labels.field'))
                            ->valueLabel(__('app.labels.permission'))
                            ->helperText(__('app.helpers.field_level_permissions')),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make(__('app.labels.layout_customization'))
                    ->schema([
                        Forms\Components\KeyValue::make('layout_overrides')
                            ->label(__('app.labels.layout_overrides'))
                            ->keyLabel(__('app.labels.view'))
                            ->valueLabel(__('app.labels.layout'))
                            ->helperText(__('app.helpers.layout_overrides')),
                        Forms\Components\KeyValue::make('custom_layouts')
                            ->label(__('app.labels.custom_layouts'))
                            ->keyLabel(__('app.labels.module'))
                            ->valueLabel(__('app.labels.layout_config'))
                            ->helperText(__('app.helpers.custom_layouts')),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make(__('app.labels.automation_settings'))
                    ->schema([
                        Forms\Components\KeyValue::make('auto_assignment_rules')
                            ->label(__('app.labels.auto_assignment_rules'))
                            ->keyLabel(__('app.labels.condition'))
                            ->valueLabel(__('app.labels.action'))
                            ->helperText(__('app.helpers.auto_assignment_rules')),
                        Forms\Components\KeyValue::make('mass_assignment_settings')
                            ->label(__('app.labels.mass_assignment_settings'))
                            ->keyLabel(__('app.labels.setting'))
                            ->valueLabel(__('app.labels.value'))
                            ->helperText(__('app.helpers.mass_assignment_settings')),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make(__('app.labels.broadcast_messaging'))
                    ->schema([
                        Forms\Components\Toggle::make('enable_broadcast')
                            ->label(__('app.labels.enable_broadcast'))
                            ->helperText(__('app.helpers.enable_broadcast_messaging')),
                        Forms\Components\KeyValue::make('broadcast_settings')
                            ->label(__('app.labels.broadcast_settings'))
                            ->keyLabel(__('app.labels.setting'))
                            ->valueLabel(__('app.labels.value'))
                            ->helperText(__('app.helpers.broadcast_settings'))
                            ->visible(fn (Forms\Get $get) => $get('enable_broadcast')),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make(__('app.labels.login_as_functionality'))
                    ->schema([
                        Forms\Components\Toggle::make('allow_login_as')
                            ->label(__('app.labels.allow_login_as'))
                            ->helperText(__('app.helpers.allow_login_as')),
                        Forms\Components\KeyValue::make('login_as_permissions')
                            ->label(__('app.labels.login_as_permissions'))
                            ->keyLabel(__('app.labels.role'))
                            ->valueLabel(__('app.labels.permission'))
                            ->helperText(__('app.helpers.login_as_permissions'))
                            ->visible(fn (Forms\Get $get) => $get('allow_login_as')),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make(__('app.labels.metadata'))
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label(__('app.labels.metadata'))
                            ->keyLabel(__('app.labels.key'))
                            ->valueLabel(__('app.labels.value'))
                            ->helperText(__('app.helpers.group_metadata')),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('app.labels.id'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('app.labels.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label(__('app.labels.parent_group'))
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('level')
                    ->label(__('app.labels.level'))
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_security_group')
                    ->label(__('app.labels.security_group'))
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_primary_group')
                    ->label(__('app.labels.primary'))
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('active')
                    ->label(__('app.labels.active'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('members_count')
                    ->label(__('app.labels.members'))
                    ->counts('members')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_security_group')
                    ->label(__('app.labels.security_groups_only')),
                Tables\Filters\TernaryFilter::make('is_primary_group')
                    ->label(__('app.labels.primary_groups_only')),
                Tables\Filters\TernaryFilter::make('active')
                    ->label(__('app.labels.active_only')),
                Tables\Filters\SelectFilter::make('parent_id')
                    ->label(__('app.labels.parent_group'))
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('level')
                    ->form([
                        Forms\Components\TextInput::make('level')
                            ->label(__('app.labels.hierarchy_level'))
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when($data['level'] ?? null, fn (Builder $q, $level): Builder => $q->where('level', $level),
                    ),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('send_broadcast')
                    ->label(__('app.actions.send_broadcast'))
                    ->icon('heroicon-o-megaphone')
                    ->color('primary')
                    ->form([
                        Forms\Components\TextInput::make('subject')
                            ->label(__('app.labels.subject'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('message')
                            ->label(__('app.labels.message'))
                            ->required()
                            ->rows(4),
                        Forms\Components\Select::make('priority')
                            ->label(__('app.labels.priority'))
                            ->options([
                                'low' => __('app.labels.low'),
                                'normal' => __('app.labels.normal'),
                                'high' => __('app.labels.high'),
                                'urgent' => __('app.labels.urgent'),
                            ])
                            ->default('normal'),
                        Forms\Components\Toggle::make('include_subgroups')
                            ->label(__('app.labels.include_subgroups'))
                            ->default(true),
                        Forms\Components\Toggle::make('require_acknowledgment')
                            ->label(__('app.labels.require_acknowledgment')),
                    ])
                    ->action(function (SecurityGroup $record, array $data): void {
                        $record->sendBroadcastMessage(
                            $data['subject'],
                            $data['message'],
                            [
                                'priority' => $data['priority'],
                                'include_subgroups' => $data['include_subgroups'],
                                'require_acknowledgment' => $data['require_acknowledgment'],
                            ],
                        );

                        Notification::make()
                            ->title(__('app.notifications.broadcast_sent'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn (SecurityGroup $record): bool => $record->enable_broadcast),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (SecurityGroup $record): void {
                        $service = resolve(SecurityGroupService::class);

                        if (! $service->validateHierarchy($record, null)) {
                            Notification::make()
                                ->title(__('app.notifications.cannot_delete_group'))
                                ->body(__('app.notifications.group_has_dependencies'))
                                ->danger()
                                ->send();

                            throw new \Exception('Cannot delete group with dependencies');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label(__('app.actions.activate'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $records->each->update(['active' => true]);

                            Notification::make()
                                ->title(__('app.notifications.groups_activated'))
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label(__('app.actions.deactivate'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $records->each->update(['active' => false]);

                            Notification::make()
                                ->title(__('app.notifications.groups_deactivated'))
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            MembersRelationManager::class,
            RecordAccessRelationManager::class,
            AuditLogsRelationManager::class,
            BroadcastMessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSecurityGroups::route('/'),
            'create' => Pages\CreateSecurityGroup::route('/create'),
            'view' => Pages\ViewSecurityGroup::route('/{record}'),
            'edit' => Pages\EditSecurityGroup::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->securityGroups()
            ->with(['parent', 'members']);
    }
}
