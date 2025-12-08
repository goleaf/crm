<?php

declare(strict_types=1);

use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\Task;
use App\Services\Example\ExampleQueryService;
use Illuminate\Support\Facades\Cache;

/**
 * Feature tests for ExampleQueryService demonstrating caching patterns.
 */
it('calculates contact metrics correctly', function (): void {
    $contact = People::factory()->create();

    Task::factory()->count(5)->create(['people_id' => $contact->id]);
    Task::factory()->count(3)->create(['people_id' => $contact->id, 'completed' => true]);
    Note::factory()->count(10)->create(['people_id' => $contact->id]);
    Opportunity::factory()->count(2)->create([
        'people_id' => $contact->id,
        'value' => 5000,
    ]);

    $service = new ExampleQueryService;
    $metrics = $service->getContactMetrics($contact);

    expect($metrics['total_tasks'])->toBe(8);
    expect($metrics['completed_tasks'])->toBe(3);
    expect($metrics['total_notes'])->toBe(10);
    expect($metrics['total_opportunities'])->toBe(2);
    expect($metrics['total_value'])->toBe(10000.0);
});

it('caches metrics for performance', function (): void {
    $contact = People::factory()->create();
    Task::factory()->count(5)->create(['people_id' => $contact->id]);

    $service = new ExampleQueryService(cacheTtl: 3600);

    // First call - should hit database
    $metrics1 = $service->getContactMetrics($contact);

    // Add more tasks
    Task::factory()->count(5)->create(['people_id' => $contact->id]);

    // Second call - should return cached value (still 5 tasks)
    $metrics2 = $service->getContactMetrics($contact);

    expect($metrics1['total_tasks'])->toBe(5);
    expect($metrics2['total_tasks'])->toBe(5); // Cached value

    // Clear cache
    $service->clearCache($contact);

    // Third call - should hit database again (now 10 tasks)
    $metrics3 = $service->getContactMetrics($contact);

    expect($metrics3['total_tasks'])->toBe(10);
});

it('clears cache correctly', function (): void {
    $contact = People::factory()->create();
    $service = new ExampleQueryService;

    // Populate cache
    $service->getContactMetrics($contact);

    expect(Cache::has("contact.metrics.{$contact->id}"))->toBeTrue();

    // Clear cache
    $service->clearCache($contact);

    expect(Cache::has("contact.metrics.{$contact->id}"))->toBeFalse();
});
