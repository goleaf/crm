<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\MergeJobStatus;
use App\Enums\MergeJobType;
use App\Filament\Resources\MergeJobResource\Pages;
use App\Models\MergeJob;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class MergeJobResource extends Resource
{
    protected static ?string $model = MergeJob::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-arrow-path-rounded-square';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.data_quality');
    }

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.merge_jobs');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.merge_job');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.merge_jobs');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('app.labels.merge_details'))
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label(__('app.labels.merge_type'))
                            ->options(MergeJobType::class)
                            ->required()
                            ->disabled(),

                        Forms\Components\TextInput::make('primary_model_type')
                            ->label(__('app.labels.primary_model'))
                            ->disabled(),

                        Forms\Components\TextInput::make('duplicate_model_type')
                            ->label(__('app.labels.duplicate_model'))
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label(__('app.labels.status'))
                            ->options(MergeJobStatus::class)
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make(__('app.labels.merge_configuration'))
                    ->schema([
                        Forms\Components\KeyValue::make('field_selections')
                            ->label(__('app.labels.field_selections'))
                            ->keyLabel(__('app.labels.field'))
                            ->valueLabel(__('app.labels.source'))
                            ->disabled(),

                        Forms\Components\KeyValue::make('transferred_relationships')
                            ->label(__('app.labels.transferred_relationships'))
                            ->keyLabel(__('app.labels.relationship'))
                            ->valueLabel(__('app.labels.count'))
                            ->disabled(),
                    ]),

                Forms\Components\Section::make(__('app.labels.processing_info'))
                    ->schema([
                        Forms\Components\Textarea::make('error_message')
                            ->label(__('app.labels.error_message'))
                            ->disabled()
                            ->visible(fn (MergeJob $record): bool => $record->isFailed()),

                        Forms\Components\DateTimePicker::make('processed_at')
                            ->label(__('app.labels.processed_at'))
                            ->disabled(),
                    ]),
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

                Tables\Columns\TextColumn::make('primary_model_type')
                    ->label(__('app.labels.primary_model'))
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('duplicate_model_type')
                    ->label(__('app.labels.duplicate_model'))
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label(__('app.labels.created_by'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('processed_at')
                    ->label(__('app.labels.processed_at'))
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
                    ->options(MergeJobType::class),

                Tables\Filters\SelectFilter::make('status')
                    ->label(__('app.labels.status'))
                    ->options(MergeJobStatus::class),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('process')
                    ->label(__('app.actions.process'))
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (MergeJob $record): bool => $record->isPending())
                    ->requiresConfirmation()
                    ->action(function (MergeJob $record): void {
                        $dataQualityService = resolve(\App\Services\DataQuality\DataQualityService::class);
                        $success = $dataQualityService->processMergeJob($record);

                        if ($success) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('app.notifications.merge_job_processed'))
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title(__('app.notifications.merge_job_failed'))
                                ->danger()
                                ->send();
                        }
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
        return parent::getEloquentQuery()->with(['createdBy', 'processedBy']);
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
            'index' => Pages\ListMergeJobs::route('/'),
            'view' => Pages\ViewMergeJob::route('/{record}'),
        ];
    }
}
