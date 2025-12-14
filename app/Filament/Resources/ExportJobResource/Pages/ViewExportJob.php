<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExportJobResource\Pages;

use App\Filament\Resources\ExportJobResource;
use App\Models\ExportJob;
use App\Services\Export\ExportService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\KeyValueEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Schema;

final class ViewExportJob extends ViewRecord
{
    protected static string $resource = ExportJobResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.sections.export_details'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('name')
                                    ->label(__('app.labels.name')),

                                TextEntry::make('model_type')
                                    ->label(__('app.labels.model_type'))
                                    ->badge(),

                                TextEntry::make('format')
                                    ->label(__('app.labels.format'))
                                    ->badge()
                                    ->color('info'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('status')
                                    ->label(__('app.labels.status'))
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'processing' => 'info',
                                        'completed' => 'success',
                                        'failed' => 'danger',
                                        default => 'gray',
                                    }),

                                TextEntry::make('scope')
                                    ->label(__('app.labels.scope'))
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'all' => __('app.labels.all_records'),
                                        'filtered' => __('app.labels.filtered_records'),
                                        'selected' => __('app.labels.selected_records'),
                                        default => $state,
                                    }),

                                TextEntry::make('progress')
                                    ->label(__('app.labels.progress'))
                                    ->getStateUsing(fn (ExportJob $record): string => $record->total_records > 0
                                            ? $record->getProgressPercentage() . '%'
                                            : '0%',
                                    ),
                            ]),
                    ]),

                Section::make(__('app.sections.statistics'))
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('total_records')
                                    ->label(__('app.labels.total_records'))
                                    ->numeric(),

                                TextEntry::make('successful_records')
                                    ->label(__('app.labels.successful_records'))
                                    ->numeric()
                                    ->color('success'),

                                TextEntry::make('failed_records')
                                    ->label(__('app.labels.failed_records'))
                                    ->numeric()
                                    ->color('danger'),

                                TextEntry::make('success_rate')
                                    ->label(__('app.labels.success_rate'))
                                    ->getStateUsing(fn (ExportJob $record): string => $record->getSuccessRate() . '%'),
                            ]),
                    ]),

                Section::make(__('app.sections.file_information'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('file_path')
                                    ->label(__('app.labels.file_path'))
                                    ->placeholder(__('app.placeholders.no_file_generated')),

                                TextEntry::make('file_size')
                                    ->label(__('app.labels.file_size'))
                                    ->getStateUsing(fn (ExportJob $record): ?string => $record->getFileSizeFormatted())
                                    ->placeholder(__('app.placeholders.no_file_generated')),

                                TextEntry::make('expires_at')
                                    ->label(__('app.labels.expires_at'))
                                    ->dateTime()
                                    ->color(fn (ExportJob $record): string => $record->isExpired() ? 'danger' : 'gray',
                                    ),
                            ]),
                    ])
                    ->visible(fn (ExportJob $record): bool => $record->isCompleted()),

                Section::make(__('app.sections.configuration'))
                    ->schema([
                        KeyValueEntry::make('selected_fields')
                            ->label(__('app.labels.selected_fields'))
                            ->visible(fn (ExportJob $record): bool => ! empty($record->selected_fields)),

                        KeyValueEntry::make('filters')
                            ->label(__('app.labels.filters'))
                            ->visible(fn (ExportJob $record): bool => ! empty($record->filters)),

                        KeyValueEntry::make('template_config')
                            ->label(__('app.labels.template_config'))
                            ->visible(fn (ExportJob $record): bool => ! empty($record->template_config)),

                        KeyValueEntry::make('options')
                            ->label(__('app.labels.options'))
                            ->visible(fn (ExportJob $record): bool => ! empty($record->options)),
                    ])
                    ->collapsible(),

                Section::make(__('app.sections.timestamps'))
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label(__('app.labels.created_at'))
                                    ->dateTime(),

                                TextEntry::make('started_at')
                                    ->label(__('app.labels.started_at'))
                                    ->dateTime()
                                    ->placeholder(__('app.placeholders.not_started')),

                                TextEntry::make('completed_at')
                                    ->label(__('app.labels.completed_at'))
                                    ->dateTime()
                                    ->placeholder(__('app.placeholders.not_completed')),

                                TextEntry::make('user.name')
                                    ->label(__('app.labels.created_by')),
                            ]),
                    ])
                    ->collapsible(),

                Section::make(__('app.sections.errors'))
                    ->schema([
                        TextEntry::make('error_message')
                            ->label(__('app.labels.error_message'))
                            ->color('danger'),

                        KeyValueEntry::make('errors')
                            ->label(__('app.labels.detailed_errors')),
                    ])
                    ->visible(fn (ExportJob $record): bool => $record->hasErrors())
                    ->collapsible(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (ExportJob $record): bool => $record->isPending()),

            Action::make('download')
                ->label(__('app.actions.download'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (ExportJob $record): bool => $record->isCompleted() && $record->file_path && ! $record->isExpired(),
                )
                ->url(fn (ExportJob $record): ?string => $record->getFileUrl())
                ->openUrlInNewTab(),

            Action::make('process')
                ->label(__('app.actions.process_export'))
                ->icon('heroicon-o-play')
                ->color('primary')
                ->visible(fn (ExportJob $record): bool => $record->isPending())
                ->action(function (ExportJob $record): void {
                    $exportService = resolve(ExportService::class);

                    try {
                        $success = $exportService->processExportJob($record);

                        if ($success) {
                            Notification::make()
                                ->title(__('app.notifications.export_processed'))
                                ->body(__('app.notifications.export_processed_body'))
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title(__('app.notifications.export_processing_failed'))
                                ->body(__('app.notifications.export_processing_failed_body'))
                                ->danger()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('app.notifications.export_processing_error'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation(),

            Action::make('retry')
                ->label(__('app.actions.retry'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (ExportJob $record): bool => $record->isFailed())
                ->action(function (ExportJob $record): void {
                    $record->update([
                        'status' => 'pending',
                        'error_message' => null,
                        'errors' => null,
                        'started_at' => null,
                        'completed_at' => null,
                        'processed_records' => 0,
                        'successful_records' => 0,
                        'failed_records' => 0,
                    ]);

                    Notification::make()
                        ->title(__('app.notifications.export_reset'))
                        ->body(__('app.notifications.export_reset_body'))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation(),
        ];
    }
}
