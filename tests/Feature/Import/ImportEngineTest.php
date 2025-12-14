<?php

declare(strict_types=1);

namespace Tests\Feature\Import;

use App\Models\ImportJob;
use App\Models\Team;
use App\Models\User;
use App\Services\Import\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ImportEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_import_job_from_csv_file(): void
    {
        // Create user and team
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $user->teams()->attach($team);
        $user->update(['current_team_id' => $team->id]);

        $this->actingAs($user);

        // Create a fake CSV file
        Storage::fake('local');
        $csvContent = "name,email,phone\nTest Company,test@example.com,123-456-7890\nAnother Company,another@example.com,098-765-4321";
        $file = UploadedFile::fake()->createWithContent('companies.csv', $csvContent);

        // Create import job
        $importService = resolve(ImportService::class);
        $importJob = $importService->createImportJob(
            $file,
            'Company',
            'Test Company Import',
            $team->id,
            $user->id,
        );

        // Assertions
        $this->assertInstanceOf(ImportJob::class, $importJob);
        $this->assertEquals('Test Company Import', $importJob->name);
        $this->assertEquals('Company', $importJob->model_type);
        $this->assertEquals('csv', $importJob->type);
        $this->assertEquals('pending', $importJob->status);
        $this->assertEquals($team->id, $importJob->team_id);
        $this->assertEquals($user->id, $importJob->user_id);

        // Check preview data was generated
        $this->assertNotNull($importJob->preview_data);
        $this->assertArrayHasKey('headers', $importJob->preview_data);
        $this->assertArrayHasKey('data', $importJob->preview_data);
        $this->assertEquals(['name', 'email', 'phone'], $importJob->preview_data['headers']);
        $this->assertCount(2, $importJob->preview_data['data']);
        $this->assertEquals(2, $importJob->total_rows);
    }

    public function test_can_validate_import_mapping(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $this->actingAs($user);

        $importJob = ImportJob::factory()->create([
            'model_type' => 'Company',
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);

        $importService = resolve(ImportService::class);

        // Test valid mapping
        $validMapping = [
            'name' => 'company_name',
            'email' => 'email_address',
        ];

        $errors = $importService->validateMapping($importJob, $validMapping);
        $this->assertEmpty($errors, 'Valid mapping should not have errors');

        // Test invalid mapping (missing required field)
        $invalidMapping = [
            'email' => 'email_address',
            // Missing required 'name' field
        ];

        $errors = $importService->validateMapping($importJob, $invalidMapping);
        $this->assertNotEmpty($errors, 'Invalid mapping should have errors');
        $this->assertStringContainsString('name', $errors[0]);
    }

    public function test_can_detect_duplicates(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $this->actingAs($user);

        // Create existing company
        \App\Models\Company::factory()->create([
            'name' => 'Existing Company',
            'email' => 'existing@example.com',
            'team_id' => $team->id,
        ]);

        $importJob = ImportJob::factory()->create([
            'model_type' => 'Company',
            'team_id' => $team->id,
            'user_id' => $user->id,
            'preview_data' => [
                'data' => [
                    [
                        'name' => 'Existing Company', // This should be detected as duplicate
                        'email' => 'different@example.com',
                    ],
                    [
                        'name' => 'New Company', // This should not be duplicate
                        'email' => 'new@example.com',
                    ],
                ],
            ],
        ]);

        $duplicateRules = [
            [
                'name' => 'Name Match',
                'fields' => ['name'],
                'match_type' => 'exact',
            ],
        ];

        $importService = resolve(ImportService::class);
        $duplicates = $importService->detectDuplicates($importJob, $duplicateRules);

        $this->assertCount(1, $duplicates, 'Should detect one duplicate');
        $this->assertEquals(0, $duplicates[0]['row_index']);
        $this->assertEquals('Existing Company', $duplicates[0]['row_data']['name']);
    }
}
