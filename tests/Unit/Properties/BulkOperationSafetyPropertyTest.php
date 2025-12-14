<?php

declare(strict_types=1);

namespace Tests\Unit\Properties;

use App\Models\Company;
use App\Models\People;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\BulkOperations\BulkOperationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: data-management, Property 3: Bulk operation safety
 *
 * Tests that bulk operations respect permissions, provide preview counts,
 * and run in batches to avoid timeouts with rollback on failure.
 */
final class BulkOperationSafetyPropertyTest extends TestCase
{
    use RefreshDatabase;

    private BulkOperationService $bulkService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bulkService = resolve(BulkOperationService::class);
    }

    /**
     * Property: Bulk operations respect user permissions
     * For any user and any set of records, bulk operations should only
     * process records the user has permission to modify.
     */
    public function test_bulk_operations_respect_permissions(): void
    {
        // Create a team and users
        $team = Team::factory()->create();
        $authorizedUser = User::factory()->create();
        $unauthorizedUser = User::factory()->create();

        $team->users()->attach($authorizedUser, ['role' => 'admin']);
        $team->users()->attach($unauthorizedUser, ['role' => 'viewer']);

        // Create test records
        $companies = Company::factory()->count(5)->create(['team_id' => $team->id]);
        $query = Company::where('team_id', $team->id);

        // Test with authorized user - should succeed
        $this->actingAs($authorizedUser);
        $result = $this->bulkService->bulkUpdate($query, ['name' => 'Updated Name'], $authorizedUser);

        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['processed_count']);
        $this->assertEquals(0, $result['failed_count']);

        // Reset data
        $companies->each->update(['name' => 'Original Name']);

        // Test with unauthorized user - should fail with permission error
        $this->expectException(\UnauthorizedAccessException::class);
        $this->bulkService->bulkUpdate($query, ['name' => 'Unauthorized Update'], $unauthorizedUser);
    }

    /**
     * Property: Bulk operations provide accurate preview counts
     * For any query, the preview should accurately reflect the number
     * of records that would be affected.
     */
    public function test_bulk_operations_provide_accurate_preview(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $team->users()->attach($user, ['role' => 'admin']);

        // Create test records with different statuses
        Task::factory()->count(10)->create([
            'team_id' => $team->id,
            'status' => 'active',
        ]);
        Task::factory()->count(5)->create([
            'team_id' => $team->id,
            'status' => 'completed',
        ]);

        // Test preview for all tasks
        $allTasksQuery = Task::where('team_id', $team->id);
        $preview = $this->bulkService->preview($allTasksQuery, 'update', ['priority' => 'high']);

        $this->assertEquals(15, $preview['total_count']);
        $this->assertEquals('update', $preview['operation']);
        $this->assertEquals(['priority' => 'high'], $preview['data']);
        $this->assertCount(10, $preview['sample_records']); // Limited to 10 samples

        // Test preview for filtered tasks
        $activeTasksQuery = Task::where('team_id', $team->id)->where('status', 'active');
        $preview = $this->bulkService->preview($activeTasksQuery, 'delete');

        $this->assertEquals(10, $preview['total_count']);
        $this->assertEquals('delete', $preview['operation']);
    }

    /**
     * Property: Bulk operations process records in batches
     * For any large set of records, bulk operations should process
     * them in configurable batches to avoid timeouts.
     */
    public function test_bulk_operations_process_in_batches(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $team->users()->attach($user, ['role' => 'admin']);

        // Create a service with small batch size for testing
        $bulkService = new BulkOperationService(batchSize: 3, maxRecords: 1000);

        // Create test records
        People::factory()->count(10)->create(['team_id' => $team->id]);
        $query = People::where('team_id', $team->id);

        $this->actingAs($user);
        $result = $bulkService->bulkUpdate($query, ['first_name' => 'Updated'], $user);

        $this->assertTrue($result['success']);
        $this->assertEquals(10, $result['processed_count']);
        $this->assertEquals(0, $result['failed_count']);

        // Verify all records were updated
        $updatedCount = People::where('team_id', $team->id)
            ->where('first_name', 'Updated')
            ->count();
        $this->assertEquals(10, $updatedCount);
    }

    /**
     * Property: Bulk operations rollback on significant failures
     * If more than 50% of records fail to process, the entire
     * operation should be rolled back.
     */
    public function test_bulk_operations_rollback_on_failure(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $team->users()->attach($user, ['role' => 'admin']);

        // Create test records
        Company::factory()->count(4)->create(['team_id' => $team->id]);

        // Create a scenario where updates will fail for some records
        // by trying to update with invalid data that violates constraints
        $query = Company::where('team_id', $team->id);

        $this->actingAs($user);

        // This should trigger a rollback since we're trying to set
        // an invalid team_id which should cause failures
        try {
            $result = $this->bulkService->bulkUpdate($query, ['team_id' => 99999], $user);

            // If it doesn't throw an exception, check that it failed appropriately
            if (isset($result['success']) && ! $result['success']) {
                $this->assertFalse($result['success']);
            }
        } catch (\RuntimeException $e) {
            $this->assertStringContains('Bulk update failed', $e->getMessage());
        }

        // Verify that no records were actually updated (rollback worked)
        $unchangedCount = Company::where('team_id', $team->id)->count();
        $this->assertEquals(4, $unchangedCount);
    }

    /**
     * Property: Bulk operations respect record limits
     * Operations should reject queries that exceed the maximum
     * allowed record count to prevent system overload.
     */
    public function test_bulk_operations_respect_record_limits(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $team->users()->attach($user, ['role' => 'admin']);

        // Create a service with very low max records for testing
        $bulkService = new BulkOperationService(batchSize: 100, maxRecords: 5);

        // Create more records than the limit
        People::factory()->count(10)->create(['team_id' => $team->id]);
        $query = People::where('team_id', $team->id);

        $this->actingAs($user);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot perform bulk operation on more than 5 records');

        $bulkService->bulkUpdate($query, ['first_name' => 'Updated'], $user);
    }

    /**
     * Property: Bulk delete operations work correctly
     * Bulk delete should respect permissions and process records safely.
     */
    public function test_bulk_delete_operations_work_correctly(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $team->users()->attach($user, ['role' => 'admin']);

        // Create test records
        Task::factory()->count(5)->create(['team_id' => $team->id]);
        $query = Task::where('team_id', $team->id);

        $this->actingAs($user);
        $result = $this->bulkService->bulkDelete($query, $user);

        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['processed_count']);
        $this->assertEquals(0, $result['failed_count']);

        // Verify records were deleted
        $remainingCount = Task::where('team_id', $team->id)->count();
        $this->assertEquals(0, $remainingCount);
    }

    /**
     * Property: Bulk assignment operations work correctly
     * Bulk assignment should update the specified field for all records.
     */
    public function test_bulk_assignment_operations_work_correctly(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $assignee = User::factory()->create();
        $team->users()->attach($user, ['role' => 'admin']);
        $team->users()->attach($assignee, ['role' => 'member']);

        // Create test tasks
        Task::factory()->count(5)->create([
            'team_id' => $team->id,
            'assigned_to' => null,
        ]);
        $query = Task::where('team_id', $team->id);

        $this->actingAs($user);
        $result = $this->bulkService->bulkAssign($query, 'assigned_to', $assignee->id, $user);

        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['processed_count']);
        $this->assertEquals(0, $result['failed_count']);

        // Verify all tasks were assigned
        $assignedCount = Task::where('team_id', $team->id)
            ->where('assigned_to', $assignee->id)
            ->count();
        $this->assertEquals(5, $assignedCount);
    }
}
