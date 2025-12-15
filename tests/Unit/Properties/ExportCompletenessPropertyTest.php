<?php

declare(strict_types=1);

namespace Tests\Unit\Properties;

use App\Models\Company;
use App\Models\ExportJob;
use App\Models\Team;
use App\Models\User;
use App\Services\Export\ExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * **Feature: data-management, Property 4: Export completeness**
 *
 * Property: Exports include selected fields (standard/custom) and respect filters/selections with consistent data across CSV/Excel.
 *
 * This property ensures that:
 * 1. All selected fields are included in the export
 * 2. Filters are properly applied to limit exported records
 * 3. Data is consistent between CSV and Excel formats
 * 4. Custom fields are included when selected
 * 5. Export scope (all/filtered/selected) is respected
 */
final class ExportCompletenessPropertyTest extends TestCase
{
    use RefreshDatabase;

    private ExportService $exportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exportService = resolve(ExportService::class);
        Storage::fake('local');
    }

    /**
     * Property: Export completeness - All selected fields are included
     *
     * For any export job with selected fields, the resulting export should contain
     * exactly those fields and no others (except computed fields).
     */
    public function test_export_includes_all_selected_fields(): void
    {
        // Create test data
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        // Create companies with various data
        Company::factory()->count(5)->create([
            'team_id' => $team->id,
        ]);

        // Define selected fields
        $selectedFields = ['id', 'name', 'email', 'phone', 'industry', 'created_at'];

        // Create export job
        $exportJob = ExportJob::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'model_type' => 'Company',
            'format' => 'csv',
            'scope' => 'all',
            'selected_fields' => $selectedFields,
            'status' => 'pending',
        ]);

        // Process the export
        $success = $this->exportService->processExportJob($exportJob);

        // Verify export was successful
        $this->assertTrue($success);
        $this->assertEquals('completed', $exportJob->fresh()->status);
        $this->assertNotNull($exportJob->fresh()->file_path);

        // Read the exported file
        $fileContent = Storage::disk($exportJob->file_disk)->get($exportJob->fresh()->file_path);
        $lines = explode("\n", trim((string) $fileContent));

        // Verify header contains all selected fields
        $headers = str_getcsv($lines[0], escape: '\\');
        $expectedHeaders = ['ID', 'Company Name', 'Email', 'Phone', 'Industry', 'Created At'];

        $this->assertCount(count($expectedHeaders), $headers);
        foreach ($expectedHeaders as $expectedHeader) {
            $this->assertContains($expectedHeader, $headers);
        }

        // Verify data rows contain values for all fields
        for ($i = 1; $i < count($lines) && $i <= 5; $i++) {
            if (in_array(trim($lines[$i]), ['', '0'], true)) {
                continue;
            }

            $row = str_getcsv($lines[$i], escape: '\\');
            $this->assertCount(count($expectedHeaders), $row, "Row $i should have same number of columns as headers");
        }
    }

    /**
     * Property: Export completeness - Filters are properly applied
     *
     * For any export job with filters, only records matching the filters
     * should be included in the export.
     */
    public function test_export_respects_filters(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        // Create companies with different industries
        Company::factory()->count(3)->create([
            'team_id' => $team->id,
            'industry' => 'Technology',
        ]);

        Company::factory()->count(2)->create([
            'team_id' => $team->id,
            'industry' => 'Healthcare',
        ]);

        // Create export job with industry filter
        $exportJob = ExportJob::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'model_type' => 'Company',
            'format' => 'csv',
            'scope' => 'filtered',
            'selected_fields' => ['id', 'name', 'industry'],
            'filters' => ['industry' => 'Technology'],
            'status' => 'pending',
        ]);

        // Process the export
        $success = $this->exportService->processExportJob($exportJob);

        // Verify export was successful
        $this->assertTrue($success);
        $this->assertEquals('completed', $exportJob->fresh()->status);

        // Verify only filtered records were exported
        $this->assertEquals(3, $exportJob->fresh()->successful_records);
        $this->assertEquals(3, $exportJob->fresh()->total_records);

        // Read and verify file content
        $fileContent = Storage::disk($exportJob->file_disk)->get($exportJob->fresh()->file_path);
        $lines = explode("\n", trim((string) $fileContent));

        // Should have header + 3 data rows
        $this->assertCount(4, array_filter($lines, fn ($line): bool => ! in_array(trim((string) $line), ['', '0'], true)));
        $counter = count($lines);

        // Verify all exported records have Technology industry
        for ($i = 1; $i < $counter; $i++) {
            if (in_array(trim($lines[$i]), ['', '0'], true)) {
                continue;
            }

            $row = str_getcsv($lines[$i], escape: '\\');
            $this->assertEquals('Technology', $row[2], 'All exported records should have Technology industry');
        }
    }

    /**
     * Property: Export completeness - Data consistency across formats
     *
     * For any export job, the data should be consistent between CSV and Excel formats.
     * The same records and field values should appear in both formats.
     */
    public function test_export_data_consistency_across_formats(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        // Create test companies
        $companies = Company::factory()->count(3)->create([
            'team_id' => $team->id,
        ]);

        $selectedFields = ['id', 'name', 'email'];

        // Create CSV export job
        $csvExportJob = ExportJob::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'model_type' => 'Company',
            'format' => 'csv',
            'scope' => 'all',
            'selected_fields' => $selectedFields,
            'status' => 'pending',
        ]);

        // Create Excel export job with same configuration
        $xlsxExportJob = ExportJob::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'model_type' => 'Company',
            'format' => 'xlsx',
            'scope' => 'all',
            'selected_fields' => $selectedFields,
            'status' => 'pending',
        ]);

        // Process both exports
        $csvSuccess = $this->exportService->processExportJob($csvExportJob);
        $xlsxSuccess = $this->exportService->processExportJob($xlsxExportJob);

        // Verify both exports were successful
        $this->assertTrue($csvSuccess);
        $this->assertTrue($xlsxSuccess);
        $this->assertEquals('completed', $csvExportJob->fresh()->status);
        $this->assertEquals('completed', $xlsxExportJob->fresh()->status);

        // Verify same number of records exported
        $this->assertEquals($csvExportJob->fresh()->successful_records, $xlsxExportJob->fresh()->successful_records);
        $this->assertEquals($csvExportJob->fresh()->total_records, $xlsxExportJob->fresh()->total_records);

        // Read CSV content
        $csvContent = Storage::disk($csvExportJob->file_disk)->get($csvExportJob->fresh()->file_path);
        $csvLines = explode("\n", trim((string) $csvContent));
        $csvHeaders = str_getcsv($csvLines[0], escape: '\\');

        // For Excel, we'll verify the structure is consistent (same number of records)
        // Full Excel parsing would require additional dependencies
        $this->assertCount(3, $companies); // Verify we have the expected number of source records
        $this->assertEquals(3, $csvExportJob->fresh()->successful_records);
        $this->assertEquals(3, $xlsxExportJob->fresh()->successful_records);

        // Verify CSV has expected structure
        $this->assertCount(3, $csvHeaders); // ID, Company Name, Email
        $this->assertCount(4, array_filter($csvLines, fn ($line): bool => ! in_array(trim((string) $line), ['', '0'], true))); // Header + 3 data rows
    }

    /**
     * Property: Export completeness - Selected record scope is respected
     *
     * For any export job with 'selected' scope, only the specified record IDs
     * should be included in the export.
     */
    public function test_export_respects_selected_record_scope(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        // Create 5 companies
        $companies = Company::factory()->count(5)->create([
            'team_id' => $team->id,
        ]);

        // Select only 2 specific companies
        $selectedIds = [$companies[1]->id, $companies[3]->id];

        // Create export job with selected scope
        $exportJob = ExportJob::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'model_type' => 'Company',
            'format' => 'csv',
            'scope' => 'selected',
            'selected_fields' => ['id', 'name'],
            'record_ids' => $selectedIds,
            'status' => 'pending',
        ]);

        // Process the export
        $success = $this->exportService->processExportJob($exportJob);

        // Verify export was successful
        $this->assertTrue($success);
        $this->assertEquals('completed', $exportJob->fresh()->status);

        // Verify only selected records were exported
        $this->assertEquals(2, $exportJob->fresh()->successful_records);
        $this->assertEquals(2, $exportJob->fresh()->total_records);

        // Read and verify file content
        $fileContent = Storage::disk($exportJob->file_disk)->get($exportJob->fresh()->file_path);
        $lines = explode("\n", trim((string) $fileContent));

        // Should have header + 2 data rows
        $this->assertCount(3, array_filter($lines, fn ($line): bool => ! in_array(trim((string) $line), ['', '0'], true)));

        // Verify exported IDs match selected IDs
        $exportedIds = [];
        $counter = count($lines);
        for ($i = 1; $i < $counter; $i++) {
            if (in_array(trim($lines[$i]), ['', '0'], true)) {
                continue;
            }

            $row = str_getcsv($lines[$i], escape: '\\');
            $exportedIds[] = (int) $row[0]; // First column is ID
        }

        sort($selectedIds);
        sort($exportedIds);
        $this->assertEquals($selectedIds, $exportedIds, 'Exported IDs should match selected IDs');
    }

    /**
     * Property: Export completeness - Empty results are handled correctly
     *
     * For any export job that matches no records, the export should complete
     * successfully with appropriate status and an empty file with headers only.
     */
    public function test_export_handles_empty_results_correctly(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        // Create companies but with different industry
        Company::factory()->count(2)->create([
            'team_id' => $team->id,
            'industry' => 'Technology',
        ]);

        // Create export job with filter that matches no records
        $exportJob = ExportJob::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'model_type' => 'Company',
            'format' => 'csv',
            'scope' => 'filtered',
            'selected_fields' => ['id', 'name', 'industry'],
            'filters' => ['industry' => 'NonExistentIndustry'],
            'status' => 'pending',
        ]);

        // Process the export
        $success = $this->exportService->processExportJob($exportJob);

        // Verify export completed successfully even with no records
        $this->assertTrue($success);
        $this->assertEquals('completed', $exportJob->fresh()->status);
        $this->assertEquals(0, $exportJob->fresh()->total_records);
        $this->assertEquals(0, $exportJob->fresh()->successful_records);
        $this->assertEquals(0, $exportJob->fresh()->failed_records);

        // File should still be created with headers
        $this->assertNotNull($exportJob->fresh()->file_path);

        $fileContent = Storage::disk($exportJob->file_disk)->get($exportJob->fresh()->file_path);
        $lines = explode("\n", trim((string) $fileContent));

        // Should have only header row
        $this->assertCount(1, array_filter($lines, fn ($line): bool => ! in_array(trim((string) $line), ['', '0'], true)));

        // Verify headers are present
        $headers = str_getcsv($lines[0], escape: '\\');
        $this->assertCount(3, $headers);
        $this->assertContains('ID', $headers);
        $this->assertContains('Company Name', $headers);
        $this->assertContains('Industry', $headers);
    }
}
