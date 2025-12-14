<?php

declare(strict_types=1);

namespace App\Services\DataQuality;

use App\Enums\DataIntegrityCheckType;
use Illuminate\Support\Facades\DB;

final class DataIntegrityService
{
    /**
     * Run a data integrity check.
     */
    public function runCheck(DataIntegrityCheckType $type, ?string $targetModel = null, array $parameters = []): array
    {
        return match ($type) {
            DataIntegrityCheckType::ORPHANED_RECORDS => $this->checkOrphanedRecords($targetModel, $parameters),
            DataIntegrityCheckType::MISSING_RELATIONSHIPS => $this->checkMissingRelationships(),
            DataIntegrityCheckType::DUPLICATE_DETECTION => $this->checkDuplicates(),
            DataIntegrityCheckType::DATA_VALIDATION => $this->checkDataValidation(),
            DataIntegrityCheckType::FOREIGN_KEY_CONSTRAINTS => $this->checkForeignKeyConstraints(),
            DataIntegrityCheckType::REQUIRED_FIELDS => $this->checkRequiredFields(),
            DataIntegrityCheckType::DATA_CONSISTENCY => $this->checkDataConsistency(),
        };
    }

    /**
     * Check for orphaned records.
     */
    private function checkOrphanedRecords(?string $targetModel, array $parameters): array
    {
        $issues = [];
        $issuesFound = 0;
        $issuesFixed = 0;

        // Define orphaned record checks for common models
        $orphanChecks = [
            \App\Models\People::class => [
                'table' => 'people',
                'foreign_keys' => [
                    'company_id' => ['table' => 'companies', 'column' => 'id'],
                ],
            ],
            \App\Models\Opportunity::class => [
                'table' => 'opportunities',
                'foreign_keys' => [
                    'company_id' => ['table' => 'companies', 'column' => 'id'],
                    'contact_id' => ['table' => 'people', 'column' => 'id'],
                ],
            ],
            \App\Models\Task::class => [
                'table' => 'tasks',
                'foreign_keys' => [
                    'creator_id' => ['table' => 'users', 'column' => 'id'],
                    'assigned_to' => ['table' => 'users', 'column' => 'id'],
                ],
            ],
        ];

        $modelsToCheck = $targetModel ? [$targetModel => $orphanChecks[$targetModel] ?? []] : $orphanChecks;

        foreach ($modelsToCheck as $model => $config) {
            if ($config === []) {
                continue;
            }

            foreach ($config['foreign_keys'] as $foreignKey => $reference) {
                $orphanedQuery = DB::table($config['table'])
                    ->leftJoin($reference['table'], $config['table'] . '.' . $foreignKey, '=', $reference['table'] . '.' . $reference['column'])
                    ->whereNotNull($config['table'] . '.' . $foreignKey)
                    ->whereNull($reference['table'] . '.' . $reference['column']);

                $orphanedCount = $orphanedQuery->count();

                if ($orphanedCount > 0) {
                    $issuesFound += $orphanedCount;
                    $issues[] = [
                        'type' => 'orphaned_records',
                        'model' => $model,
                        'table' => $config['table'],
                        'foreign_key' => $foreignKey,
                        'reference_table' => $reference['table'],
                        'count' => $orphanedCount,
                        'description' => "Found {$orphanedCount} orphaned records in {$config['table']} with invalid {$foreignKey}",
                    ];

                    // Auto-fix if requested
                    if ($parameters['auto_fix'] ?? false) {
                        if ($parameters['fix_method'] === 'delete') {
                            $deleted = $orphanedQuery->delete();
                            $issuesFixed += $deleted;
                        } elseif ($parameters['fix_method'] === 'nullify') {
                            $updated = $orphanedQuery->update([$foreignKey => null]);
                            $issuesFixed += $updated;
                        }
                    }
                }
            }
        }

        return [
            'issues_found' => $issuesFound,
            'issues_fixed' => $issuesFixed,
            'issues' => $issues,
            'summary' => "Found {$issuesFound} orphaned records" . ($issuesFixed > 0 ? ", fixed {$issuesFixed}" : ''),
        ];
    }

    /**
     * Check for missing relationships.
     */
    private function checkMissingRelationships(): array
    {
        $issues = [];
        $issuesFound = 0;
        // Check for records that should have relationships but don't
        $relationshipChecks = [
            'companies_without_contacts' => [
                'description' => 'Companies without any associated contacts',
                'query' => 'SELECT c.id, c.name FROM companies c 
                           LEFT JOIN people p ON c.id = p.company_id 
                           WHERE p.id IS NULL AND c.deleted_at IS NULL',
            ],
            'opportunities_without_contacts' => [
                'description' => 'Opportunities without associated contacts',
                'query' => 'SELECT o.id, o.title FROM opportunities o 
                           WHERE o.contact_id IS NULL AND o.deleted_at IS NULL',
            ],
        ];
        foreach ($relationshipChecks as $checkName => $config) {
            try {
                $results = DB::select($config['query']);
                $count = count($results);

                if ($count > 0) {
                    $issuesFound += $count;
                    $issues[] = [
                        'type' => 'missing_relationships',
                        'check' => $checkName,
                        'count' => $count,
                        'description' => $config['description'],
                        'records' => array_slice($results, 0, 10), // Limit to first 10 for display
                    ];
                }
            } catch (\Exception $e) {
                $issues[] = [
                    'type' => 'check_error',
                    'check' => $checkName,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'issues_found' => $issuesFound,
            'issues_fixed' => 0,
            'issues' => $issues,
            'summary' => "Found {$issuesFound} missing relationship issues",
        ];
    }

    /**
     * Check for duplicate records.
     */
    private function checkDuplicates(): array
    {
        $issues = [];
        $issuesFound = 0;
        $duplicateChecks = [
            'companies' => [
                'table' => 'companies',
                'fields' => ['name', 'website'],
                'description' => 'Companies with duplicate names or websites',
            ],
            'people' => [
                'table' => 'people',
                'fields' => ['email'],
                'description' => 'People with duplicate email addresses',
            ],
        ];
        foreach ($duplicateChecks as $config) {
            foreach ($config['fields'] as $field) {
                $duplicates = DB::table($config['table'])
                    ->select($field, DB::raw('COUNT(*) as count'))
                    ->whereNotNull($field)
                    ->where($field, '!=', '')
                    ->whereNull('deleted_at')
                    ->groupBy($field)
                    ->having('count', '>', 1)
                    ->get();

                if ($duplicates->isNotEmpty()) {
                    $totalDuplicates = $duplicates->sum('count') - $duplicates->count();
                    $issuesFound += $totalDuplicates;

                    $issues[] = [
                        'type' => 'duplicates',
                        'table' => $config['table'],
                        'field' => $field,
                        'count' => $totalDuplicates,
                        'groups' => $duplicates->count(),
                        'description' => "Found {$totalDuplicates} duplicate records in {$config['table']} based on {$field}",
                        'samples' => $duplicates->take(5)->toArray(),
                    ];
                }
            }
        }

        return [
            'issues_found' => $issuesFound,
            'issues_fixed' => 0,
            'issues' => $issues,
            'summary' => "Found {$issuesFound} duplicate records",
        ];
    }

    /**
     * Check data validation issues.
     */
    private function checkDataValidation(): array
    {
        $issues = [];
        $issuesFound = 0;
        $validationChecks = [
            'invalid_emails' => [
                'description' => 'Records with invalid email addresses',
                'query' => "SELECT 'people' as table_name, id, email FROM people 
                           WHERE email IS NOT NULL AND email != '' 
                           AND email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$'
                           AND deleted_at IS NULL
                           UNION ALL
                           SELECT 'companies' as table_name, id, primary_email as email FROM companies 
                           WHERE primary_email IS NOT NULL AND primary_email != '' 
                           AND primary_email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$'
                           AND deleted_at IS NULL",
            ],
            'invalid_phone_numbers' => [
                'description' => 'Records with invalid phone number formats',
                'query' => "SELECT 'people' as table_name, id, phone FROM people 
                           WHERE phone IS NOT NULL AND phone != '' 
                           AND phone NOT REGEXP '^[+]?[0-9\s\-\(\)\.]{7,}$'
                           AND deleted_at IS NULL",
            ],
        ];
        foreach ($validationChecks as $checkName => $config) {
            try {
                $results = DB::select($config['query']);
                $count = count($results);

                if ($count > 0) {
                    $issuesFound += $count;
                    $issues[] = [
                        'type' => 'validation_error',
                        'check' => $checkName,
                        'count' => $count,
                        'description' => $config['description'],
                        'samples' => array_slice($results, 0, 10),
                    ];
                }
            } catch (\Exception $e) {
                $issues[] = [
                    'type' => 'check_error',
                    'check' => $checkName,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'issues_found' => $issuesFound,
            'issues_fixed' => 0,
            'issues' => $issues,
            'summary' => "Found {$issuesFound} data validation issues",
        ];
    }

    /**
     * Check foreign key constraints.
     */
    private function checkForeignKeyConstraints(): array
    {
        $issues = [];
        $issuesFound = 0;
        // This would typically check database-level foreign key constraints
        // For now, we'll check logical foreign key relationships
        $constraintChecks = [
            'user_references' => [
                'description' => 'Invalid user references',
                'checks' => [
                    ['table' => 'tasks', 'column' => 'creator_id', 'reference' => 'users.id'],
                    ['table' => 'tasks', 'column' => 'assigned_to', 'reference' => 'users.id'],
                    ['table' => 'opportunities', 'column' => 'assigned_to', 'reference' => 'users.id'],
                ],
            ],
            'team_references' => [
                'description' => 'Invalid team references',
                'checks' => [
                    ['table' => 'companies', 'column' => 'team_id', 'reference' => 'teams.id'],
                    ['table' => 'people', 'column' => 'team_id', 'reference' => 'teams.id'],
                    ['table' => 'opportunities', 'column' => 'team_id', 'reference' => 'teams.id'],
                ],
            ],
        ];
        foreach ($constraintChecks as $group) {
            foreach ($group['checks'] as $check) {
                [$refTable, $refColumn] = explode('.', $check['reference']);

                $invalidCount = DB::table($check['table'])
                    ->leftJoin($refTable, $check['table'] . '.' . $check['column'], '=', $refTable . '.' . $refColumn)
                    ->whereNotNull($check['table'] . '.' . $check['column'])
                    ->whereNull($refTable . '.' . $refColumn)
                    ->count();

                if ($invalidCount > 0) {
                    $issuesFound += $invalidCount;
                    $issues[] = [
                        'type' => 'foreign_key_violation',
                        'table' => $check['table'],
                        'column' => $check['column'],
                        'reference' => $check['reference'],
                        'count' => $invalidCount,
                        'description' => "Found {$invalidCount} invalid references in {$check['table']}.{$check['column']}",
                    ];
                }
            }
        }

        return [
            'issues_found' => $issuesFound,
            'issues_fixed' => 0,
            'issues' => $issues,
            'summary' => "Found {$issuesFound} foreign key constraint violations",
        ];
    }

    /**
     * Check for missing required fields.
     */
    private function checkRequiredFields(): array
    {
        $issues = [];
        $issuesFound = 0;
        $requiredFieldChecks = [
            'companies' => ['name'],
            'people' => ['first_name', 'last_name'],
            'opportunities' => ['title'],
            'tasks' => ['title'],
        ];
        foreach ($requiredFieldChecks as $table => $fields) {
            foreach ($fields as $field) {
                $missingCount = DB::table($table)
                    ->where(function (\Illuminate\Contracts\Database\Query\Builder $query) use ($field): void {
                        $query->whereNull($field)
                            ->orWhere($field, '');
                    })
                    ->whereNull('deleted_at')
                    ->count();

                if ($missingCount > 0) {
                    $issuesFound += $missingCount;
                    $issues[] = [
                        'type' => 'missing_required_field',
                        'table' => $table,
                        'field' => $field,
                        'count' => $missingCount,
                        'description' => "Found {$missingCount} records in {$table} missing required field {$field}",
                    ];
                }
            }
        }

        return [
            'issues_found' => $issuesFound,
            'issues_fixed' => 0,
            'issues' => $issues,
            'summary' => "Found {$issuesFound} missing required field issues",
        ];
    }

    /**
     * Check data consistency issues.
     */
    private function checkDataConsistency(): array
    {
        $issues = [];
        $issuesFound = 0;
        $consistencyChecks = [
            'opportunity_amounts' => [
                'description' => 'Opportunities with negative or zero amounts',
                'query' => 'SELECT id, title, value FROM opportunities 
                           WHERE value <= 0 AND deleted_at IS NULL',
            ],
            'future_created_dates' => [
                'description' => 'Records with future creation dates',
                'query' => "SELECT 'companies' as table_name, id, name, created_at FROM companies 
                           WHERE created_at > NOW() AND deleted_at IS NULL
                           UNION ALL
                           SELECT 'people' as table_name, id, CONCAT(first_name, ' ', last_name) as name, created_at FROM people 
                           WHERE created_at > NOW() AND deleted_at IS NULL",
            ],
        ];
        foreach ($consistencyChecks as $checkName => $config) {
            try {
                $results = DB::select($config['query']);
                $count = count($results);

                if ($count > 0) {
                    $issuesFound += $count;
                    $issues[] = [
                        'type' => 'consistency_issue',
                        'check' => $checkName,
                        'count' => $count,
                        'description' => $config['description'],
                        'samples' => array_slice($results, 0, 10),
                    ];
                }
            } catch (\Exception $e) {
                $issues[] = [
                    'type' => 'check_error',
                    'check' => $checkName,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'issues_found' => $issuesFound,
            'issues_fixed' => 0,
            'issues' => $issues,
            'summary' => "Found {$issuesFound} data consistency issues",
        ];
    }
}
