<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\BackupJobStatus;
use App\Enums\BackupJobType;
use App\Filament\Resources\BackupJobResource\Pages;
use App\Models\BackupJob;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class BackupJobResource extends Resource
{
    protected static ?string $model = BackupJob::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-archive-box';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.data_quality');
    }

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.backup_jobs');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.backup_job');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.backup_jobs');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('app.labels.backup_details'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('app.labels.name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label(__('app.labels.description'))
                            ->maxLength(1000),

                        Forms\Components\Select::make('type')
                            ->label(__('app.labels.backup_type'))
                            ->options(BackupJobType::class)
                            ->required()
                            ->disabled(fn (string $operation): bool => $operation === 'edit'),

                        Forms\Components\Select::make('status')
                            ->label(__('app.labels.status'))
                            ->options(BackupJobStatus::class)
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make(__('app.labels.backup_configuration'))
                    ->schema([
                        Forms\Components\KeyValue::make('backup_config')
                            ->label(__('app.labels.configuration'))
                            ->keyLabel(__('app.labels.setting'))
                            ->valueLabel(__('app.labels.value'))
                            ->disabled(fn (string $operation): bool => $operation === 'edit'),
                    ]),

                Forms\Components\Section::make(__('app.labels.backup_info'))
                    ->schema([
                        Forms\Components\TextInput::make('backup_path')
                            ->label(__('app.labels.backup_path'))
                            ->disabled(),

                        Forms\Components\TextInput::make('file_size')
                            ->label(__('app.labels.file_size'))
                            ->formatStateUsing(fn (?int $state): string => $state ? number_format($state / 1024 / 1024, 2) . ' MB' : '—')
                            ->disabled(),

                        Forms\Components\TextInput::make('checksum')
                            ->label(__('app.labels.checksum'))
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label(__('app.labels.expires_at'))
                            ->disabled(fn (string $operation): bool => $operation === 'edit'),
                    ])->columns(2),

                Forms\Components\Section::make(__('app.labels.verification_results'))
                    ->schema([
                        Forms\Components\ViewField::make('verification_results')
                            ->label(__('app.labels.verification_results'))
                            ->view('filament.forms.components.backup-verification-results')
                            ->visible(fn (?BackupJob $record): bool => $record?->isCompleted() ?? false),

                        Forms\Components\Textarea::make('error_message')
                            ->label(__('app.labels.error_message'))
                            ->disabled()
                            ->visible(fn (?BackupJob $record): bool => $record?->isFailed() ?? false),
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

                Tables\Columns\TextColumn::make('name')
                    ->label(__('app.labels.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('app.labels.type'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('file_size')
                    ->label(__('app.labels.file_size'))
                    ->formatStateUsing(fn (?int $state): string => $state ? number_format($state / 1024 / 1024, 2) . ' MB' : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label(__('app.labels.created_by'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label(__('app.labels.completed_at'))
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label(__('app.labels.expires_at'))
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
                    ->options(BackupJobType::class),

                Tables\Filters\SelectFilter::make('status')
                    ->label(__('app.labels.status'))
                    ->options(BackupJobStatus::class),

                Tables\Filters\Filter::make('expired')
                    ->label(__('app.labels.expired'))
                    ->query(fn (Builder $query): Builder => $query->where('expires_at', '<', now())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('download')
                    ->label(__('app.actions.download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->visible(fn (BackupJob $record): bool => $record->isCompleted() && $record->backup_path && file_exists($record->backup_path))
                    ->action(fn (BackupJob $record) => response()->download($record->backup_path, basename((string) $record->backup_path))),
                Tables\Actions\Action::make('restore')
                    ->label(__('app.actions.restore'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->visible(fn (BackupJob $record): bool => $record->isCompleted())
                    ->requiresConfirmation()
                    ->modalHeading(__('app.modals.restore_backup'))
                    ->modalDescription(__('app.modals.restore_backup_description'))
                    ->action(function (BackupJob $record): void {
                        $backupService = resolve(\App\Services\DataQuality\BackupService::class);
                        $success = $backupService->restore($record);

                        if ($success) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('app.notifications.backup_restored'))
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title(__('app.notifications.backup_restore_failed'))
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
            'index' => Pages\ListBackupJobs::route('/'),
            'create' => Pages\CreateBackupJob::route('/create'),
            'view' => Pages\ViewBackupJob::route('/{record}'),
        ];
    }
}
