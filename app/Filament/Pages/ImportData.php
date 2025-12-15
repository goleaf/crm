<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\ImportJob;
use App\Services\Import\ImportMappingService;
use App\Services\Import\ImportService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Http\UploadedFile;

final class ImportData extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected string $view = 'filament.pages.import-data';

    public ?array $data = [];

    public ?ImportJob $importJob = null;

    public ?array $previewData = null;

    public ?array $suggestedMapping = null;

    public ?array $availableFields = null;

    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.data_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.import_data');
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('app.labels.import_setup'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('app.labels.name'))
                            ->required()
                            ->placeholder('My Data Import'),

                        Forms\Components\Select::make('model_type')
                            ->label(__('app.labels.model_type'))
                            ->options([
                                'Company' => __('app.labels.company'),
                                'People' => __('app.labels.people'),
                                'Contact' => __('app.labels.contact'),
                                'Lead' => __('app.labels.lead'),
                                'Opportunity' => __('app.labels.opportunity'),
                            ])
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn () => $this->resetImportState()),

                        Forms\Components\FileUpload::make('file')
                            ->label(__('app.labels.file'))
                            ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/vcard'])
                            ->maxSize(10240) // 10MB
                            ->directory('imports')
                            ->visibility('private')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->handleFileUpload()),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('app.labels.preview_data'))
                    ->schema([
                        Forms\Components\Placeholder::make('preview')
                            ->label('')
                            ->content(fn (): string => $this->renderPreview()),
                    ])
                    ->visible(fn (): bool => $this->previewData !== null),

                Forms\Components\Section::make(__('app.labels.field_mapping'))
                    ->schema([
                        Forms\Components\Repeater::make('mapping')
                            ->label('')
                            ->schema([
                                Forms\Components\Select::make('model_field')
                                    ->label(__('app.labels.model_field'))
                                    ->options(fn (): array => $this->availableFields ?? [])
                                    ->required(),

                                Forms\Components\Select::make('csv_column')
                                    ->label(__('app.labels.csv_column'))
                                    ->options(fn (): array => $this->getColumnOptions())
                                    ->required(),
                            ])
                            ->columns(2)
                            ->addActionLabel(__('app.actions.add_mapping'))
                            ->reorderable(false)
                            ->collapsible(),
                    ])
                    ->visible(fn (): bool => $this->previewData !== null),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('process')
                ->label(__('app.actions.process_import'))
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn (): bool => $this->importJob && $this->importJob->isPending())
                ->action(function () {
                    if (! $this->importJob instanceof \App\Models\ImportJob) {
                        return;
                    }

                    $importService = resolve(ImportService::class);

                    try {
                        $importService->processImport($this->importJob);

                        Notification::make()
                            ->title(__('app.notifications.import_processed'))
                            ->success()
                            ->send();

                        return redirect()->to(ImportJobResource::getUrl('view', ['record' => $this->importJob]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('app.notifications.import_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation(),
        ];
    }

    public function create(): void
    {
        $data = $this->form->getState();

        if (! isset($data['file']) || ! $data['file'] instanceof UploadedFile) {
            Notification::make()
                ->title(__('app.notifications.file_required'))
                ->danger()
                ->send();

            return;
        }

        try {
            $importService = resolve(ImportService::class);
            $this->importJob = $importService->createImportJob(
                $data['file'],
                $data['model_type'],
                $data['name'],
            );

            $this->previewData = $this->importJob->preview_data;
            $this->generateSuggestedMapping();

            Notification::make()
                ->title(__('app.notifications.import_created'))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('app.notifications.import_creation_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function handleFileUpload(): void
    {
        $this->resetImportState();
    }

    private function resetImportState(): void
    {
        $this->importJob = null;
        $this->previewData = null;
        $this->suggestedMapping = null;
        $this->availableFields = null;
    }

    private function generateSuggestedMapping(): void
    {
        if (! $this->importJob instanceof \App\Models\ImportJob) {
            return;
        }

        $mappingService = resolve(ImportMappingService::class);
        $this->suggestedMapping = $mappingService->suggestMapping($this->importJob);
        $this->availableFields = collect($mappingService->getAvailableFields($this->importJob->model_type))
            ->pluck('label', 'value')
            ->toArray();

        // Pre-fill mapping form with suggestions
        $mappingData = [];
        foreach ($this->suggestedMapping as $modelField => $csvColumn) {
            $mappingData[] = [
                'model_field' => $modelField,
                'csv_column' => $csvColumn,
            ];
        }

        $this->data['mapping'] = $mappingData;
    }

    private function renderPreview(): string
    {
        if (! $this->previewData) {
            return __('app.messages.no_preview_available');
        }

        $headers = $this->previewData['headers'] ?? [];
        $data = $this->previewData['data'] ?? [];

        if (empty($headers) || empty($data)) {
            return __('app.messages.no_preview_available');
        }

        $html = '<div class="overflow-x-auto">';
        $html .= '<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">';

        // Headers
        $html .= '<thead class="bg-gray-50 dark:bg-gray-800">';
        $html .= '<tr>';
        foreach ($headers as $header) {
            $html .= '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">';
            $html .= htmlspecialchars((string) $header);
            $html .= '</th>';
        }
        $html .= '</tr>';
        $html .= '</thead>';

        // Data rows (limit to first 5)
        $html .= '<tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">';
        foreach (array_slice($data, 0, 5) as $row) {
            $html .= '<tr>';
            foreach ($headers as $header) {
                $html .= '<td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">';
                $html .= htmlspecialchars($row[$header] ?? '');
                $html .= '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        $totalRows = $this->previewData['total_rows'] ?? count($data);
        $html .= '<p class="mt-2 text-sm text-gray-600 dark:text-gray-400">';
        $html .= __('app.messages.showing_preview_of_total', ['total' => $totalRows]);

        return $html . '</p>';
    }

    private function getColumnOptions(): array
    {
        if (! $this->previewData) {
            return [];
        }

        $headers = $this->previewData['headers'] ?? [];

        return array_combine($headers, $headers);
    }
}
