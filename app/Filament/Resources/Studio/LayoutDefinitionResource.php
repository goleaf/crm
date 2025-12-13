<?php

declare(strict_types=1);

namespace App\Filament\Resources\Studio;

use App\Filament\Clusters\Studio;
use App\Filament\Resources\Studio\LayoutDefinitionResource\Pages;
use App\Models\Studio\LayoutDefinition;
use App\Services\Studio\StudioService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class LayoutDefinitionResource extends Resource
{
    protected static ?string $model = LayoutDefinition::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $cluster = Studio::class;

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.layout_definitions');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.layout_definition');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.layout_definitions');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('app.labels.basic_information'))
                    ->schema([
                        Forms\Components\Select::make('module_name')
                            ->label(__('app.labels.module'))
                            ->options(LayoutDefinition::getAvailableModules())
                            ->required()
                            ->searchable(),

                        Forms\Components\Select::make('view_type')
                            ->label(__('app.labels.view_type'))
                            ->options(LayoutDefinition::getViewTypes())
                            ->required(),

                        Forms\Components\TextInput::make('name')
                            ->label(__('app.labels.name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label(__('app.labels.description'))
                            ->rows(3),

                        Forms\Components\Toggle::make('active')
                            ->label(__('app.labels.active'))
                            ->default(true),

                        Forms\Components\Toggle::make('system_defined')
                            ->label(__('app.labels.system_defined'))
                            ->default(false)
                            ->disabled(fn ($record) => $record?->system_defined),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('app.labels.layout_configuration'))
                    ->schema([
                        Forms\Components\KeyValue::make('components')
                            ->label(__('app.labels.components'))
                            ->keyLabel(__('app.labels.component_name'))
                            ->valueLabel(__('app.labels.component_config'))
                            ->reorderable()
                            ->addActionLabel(__('app.actions.add_component')),

                        Forms\Components\KeyValue::make('ordering')
                            ->label(__('app.labels.field_ordering'))
                            ->keyLabel(__('app.labels.field_name'))
                            ->valueLabel(__('app.labels.order_position'))
                            ->addActionLabel(__('app.actions.add_field_order')),

                        Forms\Components\KeyValue::make('visibility_rules')
                            ->label(__('app.labels.visibility_rules'))
                            ->keyLabel(__('app.labels.condition'))
                            ->valueLabel(__('app.labels.rule'))
                            ->addActionLabel(__('app.actions.add_visibility_rule')),

                        Forms\Components\KeyValue::make('group_overrides')
                            ->label(__('app.labels.group_overrides'))
                            ->keyLabel(__('app.labels.group_name'))
                            ->valueLabel(__('app.labels.override_config'))
                            ->addActionLabel(__('app.actions.add_group_override')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('module_name')
                    ->label(__('app.labels.module'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => 
                        LayoutDefinition::getAvailableModules()[$state] ?? $state
                    )
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('view_type')
                    ->label(__('app.labels.view_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => 
                        LayoutDefinition::getViewTypes()[$state] ?? $state
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('app.labels.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('active')
                    ->label(__('app.labels.active'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('system_defined')
                    ->label(__('app.labels.system_defined'))
                    ->boolean()
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
                Tables\Filters\SelectFilter::make('module_name')
                    ->label(__('app.labels.module'))
                    ->options(LayoutDefinition::getAvailableModules()),

                Tables\Filters\SelectFilter::make('view_type')
                    ->label(__('app.labels.view_type'))
                    ->options(LayoutDefinition::getViewTypes()),

                Tables\Filters\TernaryFilter::make('active')
                    ->label(__('app.labels.active')),

                Tables\Filters\TernaryFilter::make('system_defined')
                    ->label(__('app.labels.system_defined')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (LayoutDefinition $record): bool => !$record->system_defined),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each(function (LayoutDefinition $record) {
                                if (!$record->system_defined) {
                                    $record->delete();
                                }
                            });
                        }),
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
            'index' => Pages\ListLayoutDefinitions::route('/'),
            'create' => Pages\CreateLayoutDefinition::route('/create'),
            'view' => Pages\ViewLayoutDefinition::route('/{record}'),
            'edit' => Pages\EditLayoutDefinition::route('/{record}/edit'),
        ];
    }
}