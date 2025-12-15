<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\ImportJob;
use Illuminate\Database\Eloquent\Builder;

final class ImportDuplicateService
{
    public function detectDuplicates(ImportJob $importJob, array $rules): array
    {
        $modelClass = $this->getModelClass($importJob->model_type);
        if (! $modelClass) {
            return [];
        }

        $duplicates = [];
        $previewData = $importJob->preview_data['data'] ?? [];

        foreach ($previewData as $index => $row) {
            $duplicateRecords = $this->findDuplicateRecords($modelClass, $row, $rules, $importJob->team_id);

            if ($duplicateRecords !== []) {
                $duplicates[] = [
                    'row_index' => $index,
                    'row_data' => $row,
                    'duplicates' => $duplicateRecords,
                    'match_fields' => $this->getMatchedFields($row, $duplicateRecords[0] ?? [], $rules),
                ];
            }
        }

        return $duplicates;
    }

    public function getDuplicateRules(string $modelType): array
    {
        $rules = [
            'Company' => [
                [
                    'name' => 'Exact Name Match',
                    'fields' => ['name'],
                    'match_type' => 'exact',
                ],
                [
                    'name' => 'Email Match',
                    'fields' => ['email'],
                    'match_type' => 'exact',
                ],
                [
                    'name' => 'Name and Phone Match',
                    'fields' => ['name', 'phone'],
                    'match_type' => 'exact',
                ],
            ],
            'People' => [
                [
                    'name' => 'Full Name Match',
                    'fields' => ['first_name', 'last_name'],
                    'match_type' => 'exact',
                ],
                [
                    'name' => 'Email Match',
                    'fields' => ['email'],
                    'match_type' => 'exact',
                ],
                [
                    'name' => 'Name and Phone Match',
                    'fields' => ['first_name', 'last_name', 'phone'],
                    'match_type' => 'exact',
                ],
            ],
            'Lead' => [
                [
                    'name' => 'Email Match',
                    'fields' => ['email'],
                    'match_type' => 'exact',
                ],
                [
                    'name' => 'Name and Company Match',
                    'fields' => ['name', 'company'],
                    'match_type' => 'exact',
                ],
            ],
        ];

        return $rules[$modelType] ?? [];
    }

    private function findDuplicateRecords(string $modelClass, array $row, array $rules, ?int $teamId): array
    {
        $query = $modelClass::query();

        // Apply team scoping if model has team
        if (method_exists($modelClass, 'scopeForTeam') && $teamId) {
            $query->forTeam($teamId);
        }

        $conditions = [];

        foreach ($rules as $rule) {
            $ruleConditions = [];
            $hasAllFields = true;

            foreach ($rule['fields'] as $field) {
                if (! isset($row[$field]) || empty($row[$field])) {
                    $hasAllFields = false;
                    break;
                }

                $value = trim((string) $row[$field]);
                if ($rule['match_type'] === 'exact') {
                    $ruleConditions[] = [$field, '=', $value];
                } elseif ($rule['match_type'] === 'fuzzy') {
                    $ruleConditions[] = [$field, 'LIKE', "%{$value}%"];
                }
            }

            if ($hasAllFields && $ruleConditions !== []) {
                $conditions[] = $ruleConditions;
            }
        }

        if ($conditions === []) {
            return [];
        }

        // Build OR query for multiple rule sets
        $query->where(function (Builder $q) use ($conditions): void {
            foreach ($conditions as $ruleConditions) {
                $q->orWhere(function (Builder $subQ) use ($ruleConditions): void {
                    foreach ($ruleConditions as $condition) {
                        $subQ->where(...$condition);
                    }
                });
            }
        });

        return $query->limit(10)->get()->toArray();
    }

    private function getMatchedFields(array $row, array $duplicate, array $rules): array
    {
        $matchedFields = [];

        foreach ($rules as $rule) {
            $allMatch = true;
            $ruleMatches = [];

            foreach ($rule['fields'] as $field) {
                if (isset($row[$field]) && isset($duplicate[$field])) {
                    $rowValue = trim(strtolower($row[$field]));
                    $duplicateValue = trim(strtolower($duplicate[$field]));

                    if ($rule['match_type'] === 'exact' && $rowValue === $duplicateValue) {
                        $ruleMatches[] = $field;
                    } elseif ($rule['match_type'] === 'fuzzy' &&
                             (str_contains($rowValue, $duplicateValue) ||
                              str_contains($duplicateValue, $rowValue))) {
                        $ruleMatches[] = $field;
                    } else {
                        $allMatch = false;
                        break;
                    }
                } else {
                    $allMatch = false;
                    break;
                }
            }

            if ($allMatch) {
                $matchedFields = array_merge($matchedFields, $ruleMatches);
            }
        }

        return array_unique($matchedFields);
    }

    private function getModelClass(string $modelType): ?string
    {
        $modelMap = [
            'Company' => \App\Models\Company::class,
            'People' => \App\Models\People::class,
            'Contact' => \App\Models\Contact::class,
            'Lead' => \App\Models\Lead::class,
            'Opportunity' => \App\Models\Opportunity::class,
        ];

        return $modelMap[$modelType] ?? null;
    }
}
