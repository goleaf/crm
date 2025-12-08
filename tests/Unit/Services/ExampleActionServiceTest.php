<?php

declare(strict_types=1);

use App\Models\People;
use App\Services\ActivityService;
use App\Services\Example\ExampleActionService;
use App\Services\Example\ExampleQueryService;

/**
 * Unit tests for ExampleActionService demonstrating service testing patterns.
 *
 * Unit tests mock dependencies to test service logic in isolation.
 */
it('performs complex operation with mocked dependencies', function (): void {
    // Mock dependencies
    $activityService = Mockery::mock(ActivityService::class);
    $queryService = Mockery::mock(ExampleQueryService::class);

    // Set expectations
    $queryService->shouldReceive('getContactMetrics')
        ->once()
        ->andReturn([
            'total_tasks' => 5,
            'completed_tasks' => 3,
            'total_notes' => 10,
        ]);

    $activityService->shouldReceive('log')
        ->once()
        ->with(
            Mockery::type(People::class),
            'Contact updated via complex operation',
            Mockery::type('array')
        );

    // Create service with mocked dependencies
    $service = new ExampleActionService($activityService, $queryService);

    // Create test data
    $contact = People::factory()->create();

    // Execute
    $result = $service->performComplexOperation($contact, [
        'name' => 'Updated Name',
    ]);

    // Assert
    expect($result['success'])->toBeTrue();
    expect($result['contact']->name)->toBe('Updated Name');
    expect($result['metrics']['total_tasks'])->toBe(5);
});

it('rolls back transaction on failure', function (): void {
    $activityService = Mockery::mock(ActivityService::class);
    $queryService = Mockery::mock(ExampleQueryService::class);

    // Make queryService throw exception
    $queryService->shouldReceive('getContactMetrics')
        ->once()
        ->andThrow(new \Exception('Metrics calculation failed'));

    $service = new ExampleActionService($activityService, $queryService);
    $contact = People::factory()->create(['name' => 'Original Name']);

    // Expect exception
    expect(fn (): array => $service->performComplexOperation($contact, ['name' => 'New Name']))
        ->toThrow(\Exception::class);

    // Verify rollback - name should remain unchanged
    expect($contact->fresh()->name)->toBe('Original Name');
});
