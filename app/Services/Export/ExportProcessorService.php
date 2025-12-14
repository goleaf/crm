<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Models\ExportJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Csv\Writer;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ExportProcessorService
{
    /**
     * Process an export job
     */
    public function processExport(ExportJob $exportJob, Builder $query): array
    {
        try {
            $format = $exportJob->format;
            $fields = $exportJob->selected_fields ?? $this->getDefaultFields($exportJob->model_type);
            $templateConfig = $exportJob->template_config;

            // Generate file path
            $fileName = $this->generateFileName($exportJob);
            $filePath = "exports/{$fileName}";

            // Process based on format
            $result = match ($format) {
                'xlsx' => $this->processExcelExport($exportJob, $query, $fields, $filePath),
                default => $this->processCsvExport($exportJob, $query, $fields, $filePath, $templateConfig),
            };

            return $result;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'processed' => 0,
                'successful' => 0,
                'failed' => 0,
                'error_message' => $e->getMessage(),
                'errors' => ['general' => [$e->getMessage()]],
            ];
        }
    }

    /**
     * Process CSV export
     */
    private function processCsvExport(ExportJob $exportJob, Builder $query, array $fields, string $filePath, ?array $templateConfig): array
    {
        $formatOptions = $templateConfig['format_options'] ?? [
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
            'include_headers' => true,
        ];

        // Create temporary file
        $tempPath = storage_path('app/temp/' . Str::uuid() . '.csv');
        $writer = Writer::createFromPath($tempPath, 'w+');
        $writer->setDelimiter($formatOptions['delimiter']);
        $writer->setEnclosure($formatOptions['enclosure']);
        $writer->setEscape($formatOptions['escape']);

        $processed = 0;
        $successful = 0;
        $failed = 0;
        $errors = [];

        try {
            // Add headers if configured
            if ($formatOptions['include_headers']) {
                $headers = $this->generateHeaders($fields, $exportJob->model_type);
                $writer->insertOne($headers);
            }

            // Process records in chunks
            $query->chunk(1000, function ($records) use ($writer, $fields, &$processed, &$successful, &$failed, &$errors, $exportJob): void {
                foreach ($records as $record) {
                    try {
                        $row = $this->extractRecordData($record, $fields);
                        $writer->insertOne($row);
                        $successful++;
                    } catch (\Exception $e) {
                        $failed++;
                        $errors[] = "Record {$record->id}: " . $e->getMessage();
                    }

                    $processed++;

                    // Update progress periodically
                    if ($processed % 100 === 0) {
                        $exportJob->update([
                            'processed_records' => $processed,
                            'successful_records' => $successful,
                            'failed_records' => $failed,
                        ]);
                    }
                }
            });

            // Move file to final location
            Storage::disk($exportJob->file_disk)->put($filePath, file_get_contents($tempPath));
            unlink($tempPath);

            $fileSize = Storage::disk($exportJob->file_disk)->size($filePath);

            return [
                'success' => true,
                'processed' => $processed,
                'successful' => $successful,
                'failed' => $failed,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'errors' => $failed > 0 ? ['records' => $errors] : null,
            ];

        } catch (\Exception $e) {
            // Clean up temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            throw $e;
        }
    }

    /**
     * Process Excel export
     */
    private function processExcelExport(ExportJob $exportJob, Builder $query, array $fields, string $filePath): array
    {
        $spreadsheet = new Spreadsheet;
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle('Export Data');

        $processed = 0;
        $successful = 0;
        $failed = 0;
        $errors = [];
        $row = 1;

        try {
            // Add headers
            $headers = $this->generateHeaders($fields, $exportJob->model_type);
            $col = 1;
            foreach ($headers as $header) {
                $worksheet->setCellValueByColumnAndRow($col, $row, $header);
                $col++;
            }
            $row++;

            // Process records in chunks
            $query->chunk(1000, function ($records) use ($worksheet, $fields, &$processed, &$successful, &$failed, &$errors, &$row, $exportJob): void {
                foreach ($records as $record) {
                    try {
                        $data = $this->extractRecordData($record, $fields);
                        $col = 1;
                        foreach ($data as $value) {
                            $worksheet->setCellValueByColumnAndRow($col, $row, $value);
                            $col++;
                        }
                        $row++;
                        $successful++;
                    } catch (\Exception $e) {
                        $failed++;
                        $errors[] = "Record {$record->id}: " . $e->getMessage();
                    }

                    $processed++;

                    // Update progress periodically
                    if ($processed % 100 === 0) {
                        $exportJob->update([
                            'processed_records' => $processed,
                            'successful_records' => $successful,
                            'failed_records' => $failed,
                        ]);
                    }
                }
            });

            // Save to temporary file
            $tempPath = storage_path('app/temp/' . Str::uuid() . '.xlsx');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempPath);

            // Move file to final location
            Storage::disk($exportJob->file_disk)->put($filePath, file_get_contents($tempPath));
            unlink($tempPath);

            $fileSize = Storage::disk($exportJob->file_disk)->size($filePath);

            return [
                'success' => true,
                'processed' => $processed,
                'successful' => $successful,
                'failed' => $failed,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'errors' => $failed > 0 ? ['records' => $errors] : null,
            ];

        } catch (\Exception $e) {
            // Clean up temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            throw $e;
        }
    }

    /**
     * Extract data from a record based on selected fields
     */
    private function extractRecordData(\Illuminate\Database\Eloquent\Model $record, array $fields): array
    {
        $data = [];

        foreach ($fields as $field) {
            try {
                $value = $this->getFieldValue($record, $field);
                $data[] = $this->formatValue($value);
            } catch (\Exception) {
                $data[] = ''; // Empty value for failed field extraction
            }
        }

        return $data;
    }

    /**
     * Get field value from record, supporting dot notation for relationships
     */
    private function getFieldValue(\Illuminate\Database\Eloquent\Model $record, string $field): mixed
    {
        if (str_contains($field, '.')) {
            // Handle relationship fields
            $parts = explode('.', $field);
            $value = $record;

            foreach ($parts as $part) {
                if ($value === null) {
                    return null;
                }

                if (is_object($value) && method_exists($value, $part)) {
                    $value = $value->$part;
                } elseif (is_object($value) && property_exists($value, $part)) {
                    $value = $value->$part;
                } elseif (is_array($value) && isset($value[$part])) {
                    $value = $value[$part];
                } else {
                    return null;
                }
            }

            return $value;
        }

        // Handle computed fields
        switch ($field) {
            case 'people_count':
                return method_exists($record, 'people') ? $record->people()->count() : 0;
            case 'opportunities_count':
                return method_exists($record, 'opportunities') ? $record->opportunities()->count() : 0;
            case 'tasks_count':
                return method_exists($record, 'tasks') ? $record->tasks()->count() : 0;
            case 'full_name':
                if (isset($record->first_name) && isset($record->last_name)) {
                    return trim($record->first_name . ' ' . $record->last_name);
                }

                return $record->name ?? '';
            default:
                return $record->$field ?? null;
        }
    }

    /**
     * Format value for export
     */
    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value instanceof \Carbon\Carbon) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_array($value)) {
            return implode(', ', $value);
        }

        return (string) $value;
    }

    /**
     * Generate headers for export
     */
    private function generateHeaders(array $fields, string $modelType): array
    {
        $templateService = resolve(ExportTemplateService::class);
        $availableFields = $templateService->getAvailableFields($modelType);

        $headers = [];
        foreach ($fields as $field) {
            $headers[] = $availableFields[$field]['label'] ?? ucwords(str_replace(['_', '.'], ' ', $field));
        }

        return $headers;
    }

    /**
     * Generate file name for export
     */
    private function generateFileName(ExportJob $exportJob): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $modelType = strtolower($exportJob->model_type);
        $extension = $exportJob->format;

        return "{$modelType}_export_{$timestamp}.{$extension}";
    }

    /**
     * Get default fields for a model type
     */
    private function getDefaultFields(string $modelType): array
    {
        $defaultFields = [
            'Company' => ['id', 'name', 'email', 'phone', 'industry', 'created_at'],
            'People' => ['id', 'full_name', 'email', 'phone', 'job_title', 'company.name', 'created_at'],
            'Opportunity' => ['id', 'title', 'value', 'stage', 'probability', 'company.name', 'created_at'],
            'Task' => ['id', 'title', 'status', 'priority', 'due_date', 'assigned_to.name', 'created_at'],
            'Note' => ['id', 'title', 'category', 'visibility', 'creator.name', 'created_at'],
        ];

        return $defaultFields[$modelType] ?? ['id', 'created_at'];
    }
}
