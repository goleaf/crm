<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ProcessStatus;
use App\Enums\WorkflowConditionLogic;
use App\Enums\WorkflowConditionOperator;
use App\Enums\WorkflowTriggerType;
use App\Filament\Resources\WorkflowDefinitionResource\Pages;
use App\Models\WorkflowDefinition;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

final class WorkflowDefinitionResource extends Resource
{
    protected static ?string $model = WorkflowDefinition::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 100;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.automation');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.workflows');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.workflow');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.workflows');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make(__('app.sections.basic_information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('app.labels.name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label(__('app.labels.description'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->label(__('app.labels.status'))
                            ->options(ProcessStatus::class)
                            ->default(ProcessStatus::DRAFT)
                            ->required(),

                        Forms\Components\Toggle::make('test_mode')
                            ->label(__('app.labels.test_mode'))
                            ->helperText(__('app.helpers.test_mode_workflow'))
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('app.sections.trigger_configuration'))
                    ->schema([
                        Forms\Components\Select::make('trigger_type')
                            ->label(__('app.labels.trigger_type'))
                            ->options(WorkflowTriggerType::class)
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('target_model')
                            ->label(__('app.labels.target_model'))
                            ->helperText(__('app.helpers.target_model'))
                            ->placeholder(\App\Models\Lead::class)
                            ->visible(fn (Forms\Get $get): bool => in_array(
                                $get('trigger_type'),
                                [
                                    WorkflowTriggerType::ON_CREATE->value,
                                    WorkflowTriggerType::ON_EDIT->value,
                                    WorkflowTriggerType::AFTER_SAVE->value,
                                ],
                            )),

                        Forms\Components\KeyValue::make('schedule_config')
                            ->label(__('app.labels.schedule_config'))
                            ->helperText(__('app.helpers.schedule_config'))
                            ->visible(fn (Forms\Get $get): bool => $get('trigger_type') === WorkflowTriggerType::SCHEDULED->value)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('app.sections.conditions'))
                    ->schema([
                        Forms\Components\Select::make('condition_logic')
                            ->label(__('app.labels.condition_logic'))
                            ->options(WorkflowConditionLogic::class)
                            ->default(WorkflowConditionLogic::AND)
                            ->required(),

                        Forms\Components\Repeater::make('conditions')
                            ->label(__('app.labels.conditions'))
                            ->schema([
                                Forms\Components\TextInput::make('field')
                                    ->label(__('app.labels.field'))
                                    ->required()
                                    ->placeholder('status'),

                                Forms\Components\Select::make('operator')
                                    ->label(__('app.labels.operator'))
                                    ->options(WorkflowConditionOperator::class)
                                    ->required(),

                                Forms\Components\TextInput::make('value')
                                    ->label(__('app.labels.value'))
                                    ->placeholder('active'),
                            ])
                            ->columns(3)
                            ->columnSpanFull()
                            ->addActionLabel(__('app.actions.add_condition'))
                            ->collapsible()
                            ->cloneable(),
                    ]),

                Forms\Components\Section::make(__('app.sections.execution_settings'))
                    ->schema([
                        Forms\Components\Toggle::make('allow_repeated_runs')
                            ->label(__('app.labels.allow_repeated_runs'))
                            ->helperText(__('app.helpers.allow_repeated_runs'))
                            ->default(false)
                            ->live(),

                        Forms\Components\TextInput::make('max_runs_per_record')
                            ->label(__('app.labels.max_runs_per_record'))
                            ->numeric()
                            ->minValue(1)
                            ->visible(fn (Forms\Get $get) => $get('allow_repeated_runs')),

                        Forms\Components\Toggle::make('enable_logging')
                            ->label(__('app.labels.enable_logging'))
                            ->default(true),

                        Forms\Components\Select::make('log_level')
                            ->label(__('app.labels.log_level'))
                            ->options([
                                'debug' => __('app.log_levels.debug'),
                                'info' => __('app.log_levels.info'),
                                'warning' => __('app.log_levels.warning'),
                                'error' => __('app.log_levels.error'),
                            ])
                            ->default('info')
                            ->visible(fn (Forms\Get $get) => $get('enable_logging')),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('app.sections.actions'))
                    ->schema([
                        Forms\Components\Repeater::make('steps')
                            ->label(__('app.labels.workflow_actions'))
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('app.labels.name'))
                                    ->required(),

                                Forms\Components\Select::make('type')
                                    ->label(__('app.labels.action_type'))
                                    ->options([
                                        'create_record' => __('app.action_types.create_record'),
                                        'update_record' => __('app.action_types.update_record'),
                                        'send_email' => __('app.action_types.send_email'),
                                        'create_task' => __('app.action_types.create_task'),
                                        'send_notification' => __('app.action_types.send_notification'),
                                    ])
                                    ->required(),

                                Forms\Components\KeyValue::make('config')
                                    ->label(__('app.labels.configuration'))
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull()
                            ->addActionLabel(__('app.actions.add_action'))
                            ->collapsible()
                            ->reorderable()
                            ->cloneable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('app.labels.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('trigger_type')
                    ->label(__('app.labels.trigger_type'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\IconColumn::make('test_mode')
                    ->label(__('app.labels.test_mode'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('executions_count')
                    ->label(__('app.labels.executions'))
                    ->counts('executions')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('app.labels.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('app.labels.status'))
                    ->options(ProcessStatus::class),

                Tables\Filters\SelectFilter::make('trigger_type')
                    ->label(__('app.labels.trigger_type'))
                    ->options(WorkflowTriggerType::class),

                Tables\Filters\TernaryFilter::make('test_mode')
                    ->label(__('app.labels.test_mode')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkflowDefinitions::route('/'),
            'create' => Pages\CreateWorkflowDefinition::route('/create'),
            'edit' => Pages\EditWorkflowDefinition::route('/{record}/edit'),
        ];
    }
}
