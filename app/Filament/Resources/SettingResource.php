<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

final class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 999;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.system_settings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make(__('app.labels.setting_details'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('key')
                                    ->label(__('app.labels.key'))
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->helperText(__('app.helpers.setting_key')),

                                Forms\Components\Select::make('group')
                                    ->label(__('app.labels.group'))
                                    ->required()
                                    ->options([
                                        'general' => __('app.labels.general'),
                                        'company' => __('app.labels.company'),
                                        'locale' => __('app.labels.locale'),
                                        'currency' => __('app.labels.currency'),
                                        'fiscal' => __('app.labels.fiscal'),
                                        'business_hours' => __('app.labels.business_hours'),
                                        'email' => __('app.labels.email'),
                                        'scheduler' => __('app.labels.scheduler'),
                                        'notification' => __('app.labels.notification'),
                                    ])
                                    ->default('general'),

                                Forms\Components\Select::make('type')
                                    ->label(__('app.labels.type'))
                                    ->required()
                                    ->options([
                                        'string' => __('app.labels.string'),
                                        'integer' => __('app.labels.integer'),
                                        'float' => __('app.labels.float'),
                                        'boolean' => __('app.labels.boolean'),
                                        'json' => __('app.labels.json'),
                                        'array' => __('app.labels.array'),
                                    ])
                                    ->default('string')
                                    ->live(),

                                Forms\Components\Select::make('team_id')
                                    ->label(__('app.labels.team'))
                                    ->relationship('team', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->helperText(__('app.helpers.team_setting')),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label(__('app.labels.description'))
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_public')
                                    ->label(__('app.labels.is_public'))
                                    ->helperText(__('app.helpers.public_setting')),

                                Forms\Components\Toggle::make('is_encrypted')
                                    ->label(__('app.labels.is_encrypted'))
                                    ->helperText(__('app.helpers.encrypted_setting')),
                            ]),

                        Forms\Components\Textarea::make('value')
                            ->label(__('app.labels.value'))
                            ->required()
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText(__('app.helpers.setting_value')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label(__('app.labels.key'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('group')
                    ->label(__('app.labels.group'))
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('app.labels.type'))
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('value')
                    ->label(__('app.labels.value'))
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->value),

                Tables\Columns\IconColumn::make('is_public')
                    ->label(__('app.labels.public'))
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_encrypted')
                    ->label(__('app.labels.encrypted'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('team.name')
                    ->label(__('app.labels.team'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

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
                Tables\Filters\SelectFilter::make('group')
                    ->label(__('app.labels.group'))
                    ->options([
                        'general' => __('app.labels.general'),
                        'company' => __('app.labels.company'),
                        'locale' => __('app.labels.locale'),
                        'currency' => __('app.labels.currency'),
                        'fiscal' => __('app.labels.fiscal'),
                        'business_hours' => __('app.labels.business_hours'),
                        'email' => __('app.labels.email'),
                        'scheduler' => __('app.labels.scheduler'),
                        'notification' => __('app.labels.notification'),
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->label(__('app.labels.type'))
                    ->options([
                        'string' => __('app.labels.string'),
                        'integer' => __('app.labels.integer'),
                        'float' => __('app.labels.float'),
                        'boolean' => __('app.labels.boolean'),
                        'json' => __('app.labels.json'),
                        'array' => __('app.labels.array'),
                    ]),

                Tables\Filters\TernaryFilter::make('is_public')
                    ->label(__('app.labels.public')),

                Tables\Filters\TernaryFilter::make('is_encrypted')
                    ->label(__('app.labels.encrypted')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('group')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}
