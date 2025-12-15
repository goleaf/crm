<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\DataIntegrityCheckStatus;
use App\Enums\DataIntegrityCheckType;
use App\Filament\Resources\DataIntegrityCheckResource\Pages;
use App\Models\DataIntegrityCheck;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class DataIntegrityCheckResource extends Resource
{
    protected static ?string $model = DataIntegrityCheck::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-shield-check';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.data_quality');
    }

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.integrity_checks');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.integrity_check');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.integrity_checks');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('app.labels.check_details'))
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label(__('app.labels.check_type'))
                            ->options(DataIntegrityCheckType::class)
                            ->required()
                            ->disabled(fn (string $operation): bool => $operation === 'edit'),

                        Forms\Components\Select::make('status')
                            ->label(__('app.labels.status'))
                            ->options(DataIntegrityCheckStatus::class)
                            ->disabled(),

                        Forms\Components\TextInput::make('target_model')
                            ->label(__('app.labels.target_model'))
                            ->disabled(fn (string $operation): bool => $operation === 'edit'),

                        Forms\Components\TextInput::make('issues_found')
                            ->label(__('app.labels.issues_found'))
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('issues_fixed')
                            ->label(__('app.labels.issues_fixed'))
                            ->numeric()
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make(__('app.labels.check_parameters'))
                    ->schema([
                        Forms\Components\KeyValue::make('check_parameters')
                            ->label(__('app.labels.parameters'))
                            ->keyLabel(__('app.labels.parameter'))
                            ->valueLabel(__('app.labels.value'))
                            ->disabled(fn (string $operation): bool => $operation === 'edit'),
                    ]),

                Forms\Components\Section::make(__('app.labels.results'))
                    ->schema([
                        Forms\Components\Textarea::make('error_message')
                            ->label(__('app.labels.error_message'))
                            ->disabled()
                            ->visible(fn (?DataIntegrityCheck $record): bool => $record?->isFailed() ?? false),

                        Forms\Components\ViewField::make('results')
                            ->label(__('app.labels.check_results'))
                            ->view('filament.forms.components.integrity-check-results')
                            ->visible(fn (?DataIntegrityCheck $record): bool => $record?->isCompleted() ?? false),
                    ]),

                Forms\Components\Section::make(__('app.labels.timing'))
                    ->schema([
                        Forms\Components\DateTimePicker::make('started_at')
                            ->label(__('app.labels.started_at'))
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label(__('app.labels.completed_at'))
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('app.labels.id'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('app.labels.type'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('target_model')
                    ->label(__('app.labels.target_model'))
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('issues_found')
                    ->label(__('app.labels.issues_found'))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('issues_fixed')
                    ->label(__('app.labels.issues_fixed'))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label(__('app.labels.created_by'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('started_at')
                    ->label(__('app.labels.started_at'))
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label(__('app.labels.completed_at'))
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('app.labels.type'))
                    ->options(DataIntegrityCheckType::class),

                Tables\Filters\SelectFilter::make('status')
                    ->label(__('app.labels.status'))
                    ->options(DataIntegrityCheckStatus::class),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('run_check')
                    ->label(__('app.actions.run_check'))
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (DataIntegrityCheck $record): bool => $record->isPending())
                    ->requiresConfirmation()
                    ->action(function (DataIntegrityCheck $record): void {
                        $dataQualityService = resolve(\App\Services\DataQuality\DataQualityService::class);
                        $dataQualityService->runIntegrityCheck(
                            $record->type,
                            $record->target_model,
                            $record->check_parameters ?? [],
                            $record->team_id,
                        );

                        \Filament\Notifications\Notification::make()
                            ->title(__('app.notifications.integrity_check_started'))
                            ->success()
                            ->send();
                    }),
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
        return parent::getEloquentQuery()->with(['createdBy']);
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
            'index' => Pages\ListDataIntegrityChecks::route('/'),
            'create' => Pages\CreateDataIntegrityCheck::route('/create'),
            'view' => Pages\ViewDataIntegrityCheck::route('/{record}'),
        ];
    }
}
