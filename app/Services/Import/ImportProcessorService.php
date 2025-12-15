<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\ImportJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

final readonly class ImportProcessorService
{
    public function __construct(
        private ImportValidationService $validationService,
    ) {}

    public function process(ImportJob $importJob): void
    {
        $filePath = Storage::path($importJob->file_path);
        $mapping = $importJob->mapping ?? [];
        $duplicateRules = $importJob->duplicate_rules ?? [];

        if (empty($mapping)) {
            throw new \InvalidArgumentException('Import mapping is required');
        }

        $modelClass = $this->getModelClass($importJob->model_type);
        if (! $modelClass) {
            throw new \InvalidArgumentException("Invalid model type: {$importJob->model_type}");
        }

        $statistics = [
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'duplicate_rows' => 0,
            'errors' => [],
        ];

        DB::transaction(function () use ($importJob, $filePath, $mapping, $duplicateRules, $modelClass, &$statistics): void {
            $this->processFile($importJob, $filePath, $mapping, $duplicateRules, $modelClass, $statistics);
        });

        // Update import job with final statistics
        $importJob->update([
            'processed_rows' => $statistics['processed_rows'],
            'successful_rows' => $statistics['successful_rows'],
            'failed_rows' => $statistics['failed_rows'],
            'duplicate_rows' => $statistics['duplicate_rows'],
            'errors' => $statistics['errors'],
            'statistics' => $statistics,
        ]);
    }

    private function processFile(
        ImportJob $importJob,
        string $filePath,
        array $mapping,
        array $duplicateRules,
        string $modelClass,
        array &$statistics,
    ): void {
        $batchSize = 100;
        $batch = [];
        $rowIndex = 0;

        $this->readFile($importJob->type, $filePath, function (array $row) use (
            $importJob,
            $mapping,
            $duplicateRules,
            $modelClass,
            &$statistics,
            &$batch,
            &$rowIndex,
            $batchSize
        ): void {
            $rowIndex++;
            $statistics['processed_rows']++;

            // Map CSV columns to model fields
            $data = [];
            foreach ($mapping as $modelField => $csvColumn) {
                $data[$modelField] = $row[$csvColumn] ?? null;
            }

            // Add team_id if model supports it
            if (method_exists($modelClass, 'scopeForTeam')) {
                $data['team_id'] = $importJob->team_id;
            }

            // Validate row data
            $validationErrors = $this->validationService->validateRow($row, $mapping, $importJob->model_type);
            if ($validationErrors !== []) {
                $statistics['failed_rows']++;
                $statistics['errors'][] = [
                    'row' => $rowIndex,
                    'data' => $row,
                    'errors' => $validationErrors,
                ];

                return;
            }

            // Check for duplicates
            if ($duplicateRules !== [] && $this->isDuplicate($modelClass, $data, $duplicateRules, $importJob->team_id)) {
                $statistics['duplicate_rows']++;
                $statistics['errors'][] = [
                    'row' => $rowIndex,
                    'data' => $row,
                    'errors' => ['Duplicate record found'],
                ];

                return;
            }

            // Add to batch
            $batch[] = [
                'row' => $rowIndex,
                'data' => $data,
            ];

            // Process batch when it reaches the batch size
            if (count($batch) >= $batchSize) {
                $this->processBatch($modelClass, $batch, $statistics);
                $batch = [];
            }
        });

        // Process remaining batch
        if ($batch !== []) {
            $this->processBatch($modelClass, $batch, $statistics);
        }
    }

    private function processBatch(string $modelClass, array $batch, array &$statistics): void
    {
        foreach ($batch as $item) {
            try {
                $modelClass::create($item['data']);
                $statistics['successful_rows']++;
            } catch (\Exception $e) {
                $statistics['failed_rows']++;
                $statistics['errors'][] = [
                    'row' => $item['row'],
                    'data' => $item['data'],
                    'errors' => ['Database error: ' . $e->getMessage()],
                ];
            }
        }
    }

    private function readFile(string $type, string $filePath, callable $callback): void
    {
        match ($type) {
            'csv' => $this->readCsv($filePath, $callback),
            'xlsx', 'xls' => $this->readExcel($filePath, $callback),
            'vcard' => $this->readVCard($filePath, $callback),
            default => throw new \InvalidArgumentException("Unsupported file type: {$type}")
        };
    }

    private function readCsv(string $filePath, callable $callback): void
    {
        $handle = fopen($filePath, 'r');
        if (! $handle) {
            throw new \RuntimeException('Cannot open CSV file');
        }

        $headers = fgetcsv($handle, escape: '\\');

        while (($row = fgetcsv($handle, escape: '\\')) !== false) {
            $data = array_combine($headers, $row);
            $callback($data);
        }

        fclose($handle);
    }

    private function readExcel(string $filePath, callable $callback): void
    {
        Excel::import(new class($callback) implements \Maatwebsite\Excel\Concerns\ToCollection
        {
            private $callback;

            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }

            public function collection(\Illuminate\Support\Collection $collection): void
            {
                $headers = $collection->first()->toArray();

                foreach ($collection->skip(1) as $row) {
                    $data = array_combine($headers, $row->toArray());
                    ($this->callback)($data);
                }
            }
        }, $filePath);
    }

    private function readVCard(string $filePath, callable $callback): void
    {
        $content = file_get_contents($filePath);
        $vcards = $this->parseVCards($content);

        foreach ($vcards as $vcard) {
            $data = [
                'name' => $vcard['FN'] ?? $vcard['N'] ?? '',
                'email' => $vcard['EMAIL'] ?? '',
                'phone' => $vcard['TEL'] ?? '',
                'organization' => $vcard['ORG'] ?? '',
                'title' => $vcard['TITLE'] ?? '',
            ];
            $callback($data);
        }
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

    private function isDuplicate(string $modelClass, array $data, array $duplicateRules, ?int $teamId): bool
    {
        $query = $modelClass::query();

        // Apply team scoping if model has team
        if (method_exists($modelClass, 'scopeForTeam') && $teamId) {
            $query->forTeam($teamId);
        }

        foreach ($duplicateRules as $rule) {
            $ruleQuery = clone $query;
            $hasAllFields = true;

            foreach ($rule['fields'] as $field) {
                if (! isset($data[$field]) || empty($data[$field])) {
                    $hasAllFields = false;
                    break;
                }

                $value = trim((string) $data[$field]);
                if ($rule['match_type'] === 'exact') {
                    $ruleQuery->where($field, '=', $value);
                } elseif ($rule['match_type'] === 'fuzzy') {
                    $ruleQuery->where($field, 'LIKE', "%{$value}%");
                }
            }

            if ($hasAllFields && $ruleQuery->exists()) {
                return true;
            }
        }

        return false;
    }

    private function getModelClass(string $modelType): ?string
    {
        $modelMap = [
            'Company' => \App\Models\Company::class,
            'People' => \App\Models\People::class,
            'Contact' => \App\Models\Contact::class,
            'Lead' => \App\Models\Lead::class,
            'Opportunity' => \App\Models\Opportunity::class,
            'Task' => \App\Models\Task::class,
            'Note' => \App\Models\Note::class,
        ];

        return $modelMap[$modelType] ?? null;
    }
}
