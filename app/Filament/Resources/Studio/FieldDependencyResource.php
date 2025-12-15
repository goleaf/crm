<?php

declare(strict_types=1);

namespace App\Filament\Resources\Studio;

use App\Filament\Clusters\Studio;
use App\Filament\Resources\Studio\FieldDependencyResource\Pages;
use App\Models\Studio\FieldDependency;
use App\Models\Studio\LayoutDefinition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class FieldDependencyResource extends Resource
{
    protected static ?string $model = FieldDependency::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static ?string $cluster = Studio::class;

    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.field_dependencies');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.field_dependency');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.field_dependencies');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('app.labels.dependency_configuration'))
                    ->schema([
                        Forms\Components\Select::make('module_name')
                            ->label(__('app.labels.module'))
                            ->options(LayoutDefinition::getAvailableModules())
                            ->required()
                            ->searchable()
                            ->live(),

                        Forms\Components\TextInput::make('source_field_code')
                            ->label(__('app.labels.source_field'))
                            ->required()
                            ->helperText(__('app.helpers.source_field_code')),

                        Forms\Components\TextInput::make('target_field_code')
                            ->label(__('app.labels.target_field'))
                            ->required()
                            ->helperText(__('app.helpers.target_field_code')),

                        Forms\Components\Select::make('dependency_type')
                            ->label(__('app.labels.dependency_type'))
                            ->options(FieldDependency::getDependencyTypes())
                            ->required(),

                        Forms\Components\Toggle::make('active')
                            ->label(__('app.labels.active'))
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('app.labels.condition_configuration'))
                    ->schema([
                        Forms\Components\Select::make('condition_operator')
                            ->label(__('app.labels.condition_operator'))
                            ->options(FieldDependency::getConditionOperators())
                            ->required(),

                        Forms\Components\KeyValue::make('condition_value')
                            ->label(__('app.labels.condition_value'))
                            ->keyLabel(__('app.labels.key'))
                            ->valueLabel(__('app.labels.value'))
                            ->addActionLabel(__('app.actions.add_condition_value')),
                    ])
                    ->columns(1),

                Forms\Components\Section::make(__('app.labels.action_configuration'))
                    ->schema([
                        Forms\Components\Select::make('action_type')
                            ->label(__('app.labels.action_type'))
                            ->options(FieldDependency::getActionTypes())
                            ->required(),

                        Forms\Components\KeyValue::make('action_config')
                            ->label(__('app.labels.action_configuration'))
                            ->keyLabel(__('app.labels.parameter'))
                            ->valueLabel(__('app.labels.value'))
                            ->addActionLabel(__('app.actions.add_action_parameter')),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('module_name')
                    ->label(__('app.labels.module'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => LayoutDefinition::getAvailableModules()[$state] ?? $state,
                    )
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('source_field_code')
                    ->label(__('app.labels.source_field'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('target_field_code')
                    ->label(__('app.labels.target_field'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('dependency_type')
                    ->label(__('app.labels.dependency_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => FieldDependency::getDependencyTypes()[$state] ?? $state,
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('condition_operator')
                    ->label(__('app.labels.condition'))
                    ->formatStateUsing(fn (string $state): string => FieldDependency::getConditionOperators()[$state] ?? $state,
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('action_type')
                    ->label(__('app.labels.action'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => FieldDependency::getActionTypes()[$state] ?? $state,
                    )
                    ->sortable(),

                Tables\Columns\IconColumn::make('active')
                    ->label(__('app.labels.active'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('module_name')
                    ->label(__('app.labels.module'))
                    ->options(LayoutDefinition::getAvailableModules()),

                Tables\Filters\SelectFilter::make('dependency_type')
                    ->label(__('app.labels.dependency_type'))
                    ->options(FieldDependency::getDependencyTypes()),

                Tables\Filters\TernaryFilter::make('active')
                    ->label(__('app.labels.active')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('team_id', filament()->getTenant()->id);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFieldDependencies::route('/'),
            'create' => Pages\CreateFieldDependency::route('/create'),
            'view' => Pages\ViewFieldDependency::route('/{record}'),
            'edit' => Pages\EditFieldDependency::route('/{record}/edit'),
        ];
    }
}
