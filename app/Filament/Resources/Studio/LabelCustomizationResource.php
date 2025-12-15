<?php

declare(strict_types=1);

namespace App\Filament\Resources\Studio;

use App\Filament\Clusters\Studio;
use App\Filament\Resources\Studio\LabelCustomizationResource\Pages;
use App\Models\Studio\LabelCustomization;
use App\Models\Studio\LayoutDefinition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class LabelCustomizationResource extends Resource
{
    protected static ?string $model = LabelCustomization::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $cluster = Studio::class;

    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.label_customizations');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.label_customization');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.label_customizations');
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

                        Forms\Components\Select::make('element_type')
                            ->label(__('app.labels.element_type'))
                            ->options(LabelCustomization::getElementTypes())
                            ->required(),

                        Forms\Components\TextInput::make('element_key')
                            ->label(__('app.labels.element_key'))
                            ->required()
                            ->helperText(__('app.helpers.element_key')),

                        Forms\Components\Select::make('locale')
                            ->label(__('app.labels.locale'))
                            ->options([
                                'en' => __('app.languages.english'),
                                'uk' => __('app.languages.ukrainian'),
                                'ru' => __('app.languages.russian'),
                                'lt' => __('app.languages.lithuanian'),
                            ])
                            ->default('en')
                            ->required(),

                        Forms\Components\Toggle::make('active')
                            ->label(__('app.labels.active'))
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('app.labels.label_configuration'))
                    ->schema([
                        Forms\Components\TextInput::make('original_label')
                            ->label(__('app.labels.original_label'))
                            ->required()
                            ->helperText(__('app.helpers.original_label')),

                        Forms\Components\TextInput::make('custom_label')
                            ->label(__('app.labels.custom_label'))
                            ->required()
                            ->helperText(__('app.helpers.custom_label')),

                        Forms\Components\Textarea::make('description')
                            ->label(__('app.labels.description'))
                            ->rows(3)
                            ->helperText(__('app.helpers.label_description')),
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

                Tables\Columns\TextColumn::make('element_type')
                    ->label(__('app.labels.element_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => LabelCustomization::getElementTypes()[$state] ?? $state,
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('element_key')
                    ->label(__('app.labels.element_key'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('original_label')
                    ->label(__('app.labels.original_label'))
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('custom_label')
                    ->label(__('app.labels.custom_label'))
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('locale')
                    ->label(__('app.labels.locale'))
                    ->badge()
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

                Tables\Filters\SelectFilter::make('element_type')
                    ->label(__('app.labels.element_type'))
                    ->options(LabelCustomization::getElementTypes()),

                Tables\Filters\SelectFilter::make('locale')
                    ->label(__('app.labels.locale'))
                    ->options([
                        'en' => __('app.languages.english'),
                        'uk' => __('app.languages.ukrainian'),
                        'ru' => __('app.languages.russian'),
                        'lt' => __('app.languages.lithuanian'),
                    ]),

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
            'index' => Pages\ListLabelCustomizations::route('/'),
            'create' => Pages\CreateLabelCustomization::route('/create'),
            'view' => Pages\ViewLabelCustomization::route('/{record}'),
            'edit' => Pages\EditLabelCustomization::route('/{record}/edit'),
        ];
    }
}
