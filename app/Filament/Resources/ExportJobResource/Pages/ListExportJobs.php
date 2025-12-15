<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExportJobResource\Pages;

use App\Filament\Resources\ExportJobResource;
use App\Services\Export\ExportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

final class ListExportJobs extends ListRecords
{
    protected static string $resource = ExportJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('app.actions.create_export'))
                ->icon('heroicon-o-plus')
                ->form([
                    Section::make(__('app.sections.export_configuration'))
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('name')
                                        ->label(__('app.labels.export_name'))
                                        ->required()
                                        ->placeholder(__('app.placeholders.export_name'))
                                        ->maxLength(255),

                                    Select::make('model_type')
                                        ->label(__('app.labels.model_type'))
                                        ->options([
                                            'Company' => __('app.models.company'),
                                            'People' => __('app.models.people'),
                                            'Opportunity' => __('app.models.opportunity'),
                                            'Task' => __('app.models.task'),
                                            'Note' => __('app.models.note'),
                                        ])
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(fn ($state, callable $set) => $set('selected_fields', null)),
                                ]),

                            Grid::make(2)
                                ->schema([
                                    Select::make('format')
                                        ->label(__('app.labels.format'))
                                        ->options([
                                            'csv' => 'CSV',
                                            'xlsx' => 'Excel (XLSX)',
                                        ])
                                        ->default('csv')
                                        ->required(),

                                    Select::make('scope')
                                        ->label(__('app.labels.scope'))
                                        ->options([
                                            'all' => __('app.labels.all_records'),
                                            'filtered' => __('app.labels.filtered_records'),
                                        ])
                                        ->default('all')
                                        ->required()
                                        ->live(),
                                ]),
                        ]),

                    Section::make(__('app.sections.field_selection'))
                        ->schema([
                            CheckboxList::make('selected_fields')
                                ->label(__('app.labels.fields_to_export'))
                                ->options(function (Get $get): array {
                                    $modelType = $get('model_type');
                                    if (! $modelType) {
                                        return [];
                                    }

                                    $exportService = resolve(ExportService::class);
                                    $fields = $exportService->getAvailableFields($modelType);

                                    $options = [];
                                    foreach ($fields as $key => $field) {
                                        $options[$key] = $field['label'];
                                    }

                                    return $options;
                                })
                                ->columns(3)
                                ->required()
                                ->helperText(__('app.helpers.select_fields_to_export')),
                        ])
                        ->visible(fn (Get $get): bool => ! empty($get('model_type'))),

                    Section::make(__('app.sections.filters'))
                        ->schema([
                            KeyValue::make('filters')
                                ->label(__('app.labels.filters'))
                                ->helperText(__('app.helpers.export_filters_help'))
                                ->keyLabel(__('app.labels.field'))
                                ->valueLabel(__('app.labels.value')),
                        ])
                        ->visible(fn (Get $get): bool => $get('scope') === 'filtered'),

                    Section::make(__('app.sections.options'))
                        ->schema([
                            Checkbox::make('include_headers')
                                ->label(__('app.labels.include_headers'))
                                ->default(true)
                                ->helperText(__('app.helpers.include_headers_help')),

                            Select::make('date_format')
                                ->label(__('app.labels.date_format'))
                                ->options([
                                    'Y-m-d H:i:s' => 'YYYY-MM-DD HH:MM:SS',
                                    'Y-m-d' => 'YYYY-MM-DD',
                                    'd/m/Y' => 'DD/MM/YYYY',
                                    'm/d/Y' => 'MM/DD/YYYY',
                                ])
                                ->default('Y-m-d H:i:s'),
                        ]),
                ])
                ->action(function (array $data): void {
                    $exportService = resolve(ExportService::class);

                    // Prepare export configuration
                    $config = [
                        'name' => $data['name'],
                        'model_type' => $data['model_type'],
                        'format' => $data['format'],
                        'scope' => $data['scope'],
                        'selected_fields' => $data['selected_fields'],
                        'filters' => $data['filters'] ?? null,
                        'options' => [
                            'include_headers' => $data['include_headers'] ?? true,
                            'date_format' => $data['date_format'] ?? 'Y-m-d H:i:s',
                        ],
                    ];

                    try {
                        $exportJob = $exportService->createExportJob($config);

                        Notification::make()
                            ->title(__('app.notifications.export_job_created'))
                            ->body(__('app.notifications.export_job_created_body', ['name' => $exportJob->name]))
                            ->success()
                            ->send();

                        // Optionally start processing immediately
                        // dispatch(new ProcessExportJob($exportJob));

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('app.notifications.export_job_creation_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->modalWidth('4xl'),

            Action::make('cleanup_expired')
                ->label(__('app.actions.cleanup_expired'))
                ->icon('heroicon-o-trash')
                ->color('warning')
                ->action(function (): void {
                    $exportService = resolve(ExportService::class);
                    $cleanedCount = $exportService->cleanupExpiredExports();

                    Notification::make()
                        ->title(__('app.notifications.cleanup_completed'))
                        ->body(__('app.notifications.cleanup_completed_body', ['count' => $cleanedCount]))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalDescription(__('app.modals.cleanup_expired_exports_description')),
        ];
    }
}
