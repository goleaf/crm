<?php

declare(strict_types=1);

namespace App\Filament\Resources\ImportJobResource\Pages;

use App\Filament\Resources\ImportJobResource;
use App\Services\Import\ImportService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\KeyValueEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Schema;

final class ViewImportJob extends ViewRecord
{
    protected static string $resource = ImportJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('process')
                ->label(__('app.actions.process_import'))
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn (): bool => $this->record->isPending())
                ->action(function (): void {
                    $importService = resolve(ImportService::class);
                    $importService->processImport($this->record);
                    $this->refreshFormData(['status', 'processed_rows', 'successful_rows', 'failed_rows']);
                })
                ->requiresConfirmation(),

            Actions\Action::make('download_errors')
                ->label(__('app.actions.download_errors'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->visible(fn (): bool => $this->record->failed_rows > 0)
                ->action(fn () => response()->streamDownload(function (): void {
                    $errors = $this->record->errors ?? [];
                    $csv = fopen('php://output', 'w');

                    // Write header
                    fputcsv($csv, ['Row', 'Error', 'Data'], escape: '\\');

                    // Write errors
                    foreach ($errors as $error) {
                        fputcsv($csv, [
                            $error['row'] ?? '',
                            implode('; ', $error['errors'] ?? []),
                            json_encode($error['data'] ?? []),
                        ],
                            escape: '\\');
                    }

                    fclose($csv);
                }, "import-errors-{$this->record->id}.csv", [
                    'Content-Type' => 'text/csv',
                ])),

            Actions\EditAction::make()
                ->visible(fn (): bool => $this->record->isPending()),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.labels.import_details'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label(__('app.labels.name')),

                                TextEntry::make('model_type')
                                    ->label(__('app.labels.model_type'))
                                    ->badge(),

                                TextEntry::make('type')
                                    ->label(__('app.labels.file_type'))
                                    ->badge(),

                                TextEntry::make('status')
                                    ->label(__('app.labels.status'))
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'gray',
                                        'processing' => 'warning',
                                        'completed' => 'success',
                                        'failed' => 'danger',
                                        default => 'gray',
                                    }),

                                TextEntry::make('original_filename')
                                    ->label(__('app.labels.original_filename')),

                                TextEntry::make('file_size')
                                    ->label(__('app.labels.file_size'))
                                    ->formatStateUsing(fn ($state): string => $state ? number_format($state / 1024, 2) . ' KB' : ''),

                                TextEntry::make('user.name')
                                    ->label(__('app.labels.created_by')),

                                TextEntry::make('created_at')
                                    ->label(__('app.labels.created_at'))
                                    ->dateTime(),
                            ]),
                    ]),

                Section::make(__('app.labels.statistics'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('total_rows')
                                    ->label(__('app.labels.total_rows'))
                                    ->numeric(),

                                TextEntry::make('processed_rows')
                                    ->label(__('app.labels.processed_rows'))
                                    ->numeric(),

                                TextEntry::make('successful_rows')
                                    ->label(__('app.labels.successful_rows'))
                                    ->numeric()
                                    ->color('success'),

                                TextEntry::make('failed_rows')
                                    ->label(__('app.labels.failed_rows'))
                                    ->numeric()
                                    ->color('danger'),

                                TextEntry::make('duplicate_rows')
                                    ->label(__('app.labels.duplicate_rows'))
                                    ->numeric()
                                    ->color('warning'),

                                TextEntry::make('success_rate')
                                    ->label(__('app.labels.success_rate'))
                                    ->formatStateUsing(fn (): string => number_format($this->record->getSuccessRate(), 1) . '%')
                                    ->color('success'),
                            ]),
                    ])
                    ->visible(fn (): bool => $this->record->total_rows > 0),

                Section::make(__('app.labels.preview_data'))
                    ->schema([
                        KeyValueEntry::make('preview_data.headers')
                            ->label(__('app.labels.headers'))
                            ->visible(fn (): bool => ! empty($this->record->preview_data['headers'] ?? [])),

                        TextEntry::make('preview_sample')
                            ->label(__('app.labels.sample_data'))
                            ->formatStateUsing(function (): string|array|null {
                                $previewData = $this->record->preview_data['data'] ?? [];
                                if (empty($previewData)) {
                                    return __('app.messages.no_preview_available');
                                }

                                $html = '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200">';

                                // Headers
                                if (! empty($this->record->preview_data['headers'])) {
                                    $html .= '<thead class="bg-gray-50"><tr>';
                                    foreach ($this->record->preview_data['headers'] as $header) {
                                        $html .= '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">' . htmlspecialchars($header) . '</th>';
                                    }
                                    $html .= '</tr></thead>';
                                }

                                // Data rows
                                $html .= '<tbody class="bg-white divide-y divide-gray-200">';
                                foreach (array_slice($previewData, 0, 5) as $row) {
                                    $html .= '<tr>';
                                    foreach ($row as $cell) {
                                        $html .= '<td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($cell ?? '') . '</td>';
                                    }
                                    $html .= '</tr>';
                                }

                                return $html . '</tbody></table></div>';
                            })
                            ->html()
                            ->visible(fn (): bool => ! empty($this->record->preview_data['data'] ?? [])),
                    ])
                    ->visible(fn (): bool => ! empty($this->record->preview_data)),

                Section::make(__('app.labels.errors'))
                    ->schema([
                        TextEntry::make('error_summary')
                            ->label(__('app.labels.error_summary'))
                            ->formatStateUsing(function (): string|array|null {
                                $errors = $this->record->errors ?? [];
                                if (empty($errors)) {
                                    return __('app.messages.no_errors');
                                }

                                $html = '<div class="space-y-2">';
                                foreach (array_slice($errors, 0, 10) as $error) {
                                    $html .= '<div class="p-2 bg-red-50 border border-red-200 rounded">';
                                    $html .= '<strong>Row ' . ($error['row'] ?? 'Unknown') . ':</strong> ';
                                    $html .= implode(', ', $error['errors'] ?? []);
                                    $html .= '</div>';
                                }

                                if (count($errors) > 10) {
                                    $html .= '<div class="text-sm text-gray-500">... and ' . (count($errors) - 10) . ' more errors</div>';
                                }

                                return $html . '</div>';
                            })
                            ->html(),
                    ])
                    ->visible(fn (): bool => $this->record->failed_rows > 0),
            ]);
    }
}
