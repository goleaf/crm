<?php

declare(strict_types=1);

namespace Tests\Unit\Properties;

use App\Models\Company;
use App\Models\ImportJob;
use App\Services\Import\ImportDuplicateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: data-management, Property 2: Duplicate prevention
 *
 * Property: Duplicate detection during import/export and merge operations prevents duplicate records according to configured rules.
 * Validates: Requirements 1.1
 */
final class DuplicatePreventionPropertyTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_prevention_property(): void
    {
        $this->markTestIncomplete('Property test implementation needed');

        // Property: For any import with duplicate detection rules,
        // records that match existing data according to the rules
        // should be identified as duplicates

        $duplicateService = resolve(ImportDuplicateService::class);

        // Create existing company in database
        $existingCompany = Company::factory()->create([
            'name' => 'Existing Company',
            'email' => 'existing@example.com',
        ]);

        // Create import job with duplicate rules
        $importJob = ImportJob::factory()->create([
            'model_type' => 'Company',
            'team_id' => $existingCompany->team_id,
            'preview_data' => [
                'data' => [
                    // This should be detected as duplicate (exact name match)
                    [
                        'name' => 'Existing Company',
                        'email' => 'different@example.com',
                    ],
                    // This should be detected as duplicate (email match)
                    [
                        'name' => 'Different Company',
                        'email' => 'existing@example.com',
                    ],
                    // This should NOT be detected as duplicate
                    [
                        'name' => 'New Company',
                        'email' => 'new@example.com',
                    ],
                ],
            ],
        ]);

        // Define duplicate detection rules
        $duplicateRules = [
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
        ];

        // Detect duplicates
        $duplicates = $duplicateService->detectDuplicates($importJob, $duplicateRules);

        // Property assertions
        $this->assertIsArray($duplicates);

        // Should detect 2 duplicates (name match and email match)
        $this->assertCount(2, $duplicates, 'Should detect exactly 2 duplicate records');

        // Each duplicate should have proper structure
        foreach ($duplicates as $duplicate) {
            $this->assertArrayHasKey('row_index', $duplicate);
            $this->assertArrayHasKey('row_data', $duplicate);
            $this->assertArrayHasKey('duplicates', $duplicate);
            $this->assertArrayHasKey('match_fields', $duplicate);

            // Should have found matching records
            $this->assertNotEmpty($duplicate['duplicates']);
            $this->assertNotEmpty($duplicate['match_fields']);
        }

        // Verify the third row (new company) is not in duplicates
        $duplicateRowIndices = array_column($duplicates, 'row_index');
        $this->assertNotContains(2, $duplicateRowIndices, 'New company should not be detected as duplicate');
    }
}
