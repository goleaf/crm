<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\ImportJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

final readonly class ImportService
{
    public function __construct(
        private ImportValidationService $validationService,
        private ImportDuplicateService $duplicateService,
        private ImportProcessorService $processorService,
    ) {}

    public function createImportJob(
        UploadedFile $file,
        string $modelType,
        string $name,
        ?int $teamId = null,
        ?int $userId = null,
    ): ImportJob {
        $teamId ??= auth()->user()?->currentTeam?->id;
        $userId ??= auth()->id();

        // Store the uploaded file
        $filePath = $file->store('imports', 'local');

        // Detect file type
        $type = $this->detectFileType($file);

        // Create import job
        $importJob = ImportJob::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'name' => $name,
            'type' => $type,
            'model_type' => $modelType,
            'file_path' => $filePath,
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'status' => 'pending',
        ]);

        // Generate preview data
        $this->generatePreview($importJob);

        return $importJob;
    }

    public function generatePreview(ImportJob $importJob, int $rows = 5): void
    {
        $filePath = Storage::path($importJob->file_path);

        try {
            $previewData = match ($importJob->type) {
                'csv' => $this->previewCsv($filePath, $rows),
                'xlsx', 'xls' => $this->previewExcel($filePath, $rows),
                'vcard' => $this->previewVCard($filePath, $rows),
                default => throw new \InvalidArgumentException("Unsupported file type: {$importJob->type}")
            };

            $importJob->update([
                'preview_data' => $previewData,
                'total_rows' => $previewData['total_rows'] ?? 0,
            ]);
        } catch (\Exception $e) {
            $importJob->update([
                'status' => 'failed',
                'errors' => [['message' => 'Failed to generate preview: ' . $e->getMessage()]],
            ]);
        }
    }

    public function validateMapping(ImportJob $importJob, array $mapping): array
    {
        return $this->validationService->validateMapping($importJob, $mapping);
    }

    public function processImport(ImportJob $importJob): void
    {
        $importJob->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            $this->processorService->process($importJob);

            $importJob->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } catch (\Exception $e) {
            $importJob->update([
                'status' => 'failed',
                'completed_at' => now(),
                'errors' => array_merge($importJob->errors ?? [], [
                    ['message' => 'Import failed: ' . $e->getMessage()],
                ]),
            ]);
        }
    }

    public function detectDuplicates(ImportJob $importJob, array $rules): array
    {
        return $this->duplicateService->detectDuplicates($importJob, $rules);
    }

    private function detectFileType(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'csv' => 'csv',
            'xlsx' => 'xlsx',
            'xls' => 'xls',
            'vcf' => 'vcard',
            default => throw new \InvalidArgumentException("Unsupported file extension: {$extension}")
        };
    }

    private function previewCsv(string $filePath, int $rows): array
    {
        $handle = fopen($filePath, 'r');
        if (! $handle) {
            throw new \RuntimeException('Cannot open CSV file');
        }

        $headers = fgetcsv($handle, escape: '\\');
        $data = [];
        $totalRows = 0;

        // Read preview rows
        for ($i = 0; $i < $rows && ! feof($handle); $i++) {
            $row = fgetcsv($handle, escape: '\\');
            if ($row !== false) {
                $data[] = array_combine($headers, $row);
            }
        }

        // Count total rows
        while (fgetcsv($handle, escape: '\\') !== false) {
            $totalRows++;
        }
        $totalRows += count($data); // Add preview rows to total

        fclose($handle);

        return [
            'headers' => $headers,
            'data' => $data,
            'total_rows' => $totalRows,
        ];
    }

    private function previewExcel(string $filePath, int $rows): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        $headers = [];
        $data = [];
        $totalRows = $worksheet->getHighestRow() - 1; // Exclude header row

        // Get headers from first row
        $headerRow = $worksheet->rangeToArray('A1:' . $worksheet->getHighestColumn() . '1')[0];
        $headers = array_filter($headerRow, fn ($value): bool => ! is_null($value) && $value !== '');

        // Get preview data
        $maxRow = min($rows + 1, $worksheet->getHighestRow());
        for ($row = 2; $row <= $maxRow; $row++) {
            $rowData = $worksheet->rangeToArray('A' . $row . ':' . $worksheet->getHighestColumn() . $row)[0];
            $data[] = array_combine($headers, array_slice($rowData, 0, count($headers)));
        }

        return [
            'headers' => $headers,
            'data' => $data,
            'total_rows' => $totalRows,
        ];
    }

    private function previewVCard(string $filePath, int $rows): array
    {
        $content = file_get_contents($filePath);
        $vcards = $this->parseVCards($content);

        $headers = ['name', 'email', 'phone', 'organization', 'title'];
        $data = [];

        foreach (array_slice($vcards, 0, $rows) as $vcard) {
            $data[] = [
                'name' => $vcard['FN'] ?? $vcard['N'] ?? '',
                'email' => $vcard['EMAIL'] ?? '',
                'phone' => $vcard['TEL'] ?? '',
                'organization' => $vcard['ORG'] ?? '',
                'title' => $vcard['TITLE'] ?? '',
            ];
        }

        return [
            'headers' => $headers,
            'data' => $data,
            'total_rows' => count($vcards),
        ];
    }

    private function parseVCards(string $content): array
    {
        $vcards = [];
        $lines = explode("\n", $content);
        $currentVCard = [];
        $inVCard = false;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === 'BEGIN:VCARD') {
                $inVCard = true;
                $currentVCard = [];
            } elseif ($line === 'END:VCARD') {
                if ($inVCard && $currentVCard !== []) {
                    $vcards[] = $currentVCard;
                }
                $inVCard = false;
            } elseif ($inVCard && str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $currentVCard[trim($key)] = trim($value);
            }
        }

        return $vcards;
    }
}
