<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Generators\CalendarEventGenerator;
use Tests\TestCase;

/**
 * Performance tests for CalendarEventGenerator.
 *
 * These tests ensure the generator performs efficiently under various load conditions
 * and doesn't have performance regressions.
 */
final class CalendarEventGeneratorPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::factory()->create();
        $this->user = User::factory()->create();
        $this->team->users()->attach($this->user);
    }

    public function test_single_event_generation_performance(): void
    {
        $startTime = microtime(true);

        $event = CalendarEventGenerator::generate($this->team, $this->user);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertLessThan(0.1, $executionTime, 'Single event generation should complete within 100ms');
        $this->assertNotNull($event->id);
    }

    public function test_bulk_generation_performance(): void
    {
        $counts = [10, 25, 50, 100];

        foreach ($counts as $count) {
            $startTime = microtime(true);

            $events = CalendarEventGenerator::generateMultiple($this->team, $count, $this->user);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            $maxTime = $count * 0.05; // 50ms per event max
            $this->assertLessThan($maxTime, $executionTime,
                "Generating {$count} events should complete within {$maxTime}s");
            $this->assertCount($count, $events);
        }
    }

    public function test_recurring_event_generation_performance(): void
    {
        $startTime = microtime(true);

        $parentEvent = CalendarEventGenerator::generateRecurring($this->team, $this->user);

        // Generate 10 instances
        $instances = [];
        for ($i = 1; $i <= 10; $i++) {
            $instanceStartAt = $parentEvent->start_at->copy()->addWeeks($i);
            $instances[] = CalendarEventGenerator::generateRecurringInstance($parentEvent, $instanceStartAt);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertLessThan(2.0, $executionTime, 'Recurring event with 10 instances should complete within 2s');
        $this->assertCount(10, $instances);
        $this->assertTrue($parentEvent->isRecurring());
    }

    public function test_memory_usage_during_bulk_generation(): void
    {
        $initialMemory = memory_get_usage(true);

        // Generate a large number of events
        $events = CalendarEventGenerator::generateMultiple($this->team, 200, $this->user);

        $peakMemory = memory_get_peak_usage(true);
        $memoryIncrease = $peakMemory - $initialMemory;

        // Memory increase should be reasonable (less than 50MB for 200 events)
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease,
            'Memory usage should not exceed 50MB for 200 events');
        $this->assertCount(200, $events);

        // Clean up to free memory
        unset($events);

        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    public function test_database_query_efficiency(): void
    {
        // Enable query logging
        \DB::enableQueryLog();

        // Generate multiple events
        $events = CalendarEventGenerator::generateMultiple($this->team, 20, $this->user);

        $queries = \DB::getQueryLog();
        $queryCount = count($queries);

        // Should not have excessive queries (N+1 problems)
        // Each event creation should be roughly 1-2 queries
        $this->assertLessThan(50, $queryCount,
            'Should not execute more than 50 queries for 20 events');

        $this->assertCount(20, $events);

        // Disable query logging
        \DB::disableQueryLog();
    }

    public function test_concurrent_generation_simulation(): void
    {
        // Simulate concurrent generation by creating events rapidly
        $startTime = microtime(true);

        $events = [];
        for ($i = 0; $i < 50; $i++) {
            $events[] = CalendarEventGenerator::generate($this->team, $this->user);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertLessThan(5.0, $executionTime, 'Rapid generation should complete within 5s');
        $this->assertCount(50, $events);

        // Verify all events are unique
        $ids = collect($events)->pluck('id')->unique();
        $this->assertCount(50, $ids);
    }

    public function test_edge_case_generation_performance(): void
    {
        $startTime = microtime(true);

        $events = [];
        for ($i = 0; $i < 20; $i++) {
            $events[] = CalendarEventGenerator::generateEdgeCase($this->team, $this->user);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertLessThan(3.0, $executionTime, 'Edge case generation should complete within 3s');
        $this->assertCount(20, $events);
    }

    public function test_data_generation_without_persistence_performance(): void
    {
        $startTime = microtime(true);

        $dataArrays = [];
        for ($i = 0; $i < 100; $i++) {
            $dataArrays[] = CalendarEventGenerator::generateData($this->team, $this->user);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertLessThan(1.0, $executionTime,
            'Data generation without persistence should complete within 1s for 100 items');
        $this->assertCount(100, $dataArrays);

        // Verify no events were actually created in database
        $this->assertEquals(0, \App\Models\CalendarEvent::count());
    }

    public function test_generator_scalability(): void
    {
        $scalabilityTests = [
            ['count' => 10, 'maxTime' => 1.0],
            ['count' => 50, 'maxTime' => 3.0],
            ['count' => 100, 'maxTime' => 5.0],
            ['count' => 200, 'maxTime' => 10.0],
        ];

        foreach ($scalabilityTests as $test) {
            $startTime = microtime(true);

            $events = CalendarEventGenerator::generateMultiple(
                $this->team,
                $test['count'],
                $this->user,
            );

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            $this->assertLessThan($test['maxTime'], $executionTime,
                "Generating {$test['count']} events should complete within {$test['maxTime']}s");
            $this->assertCount($test['count'], $events);

            // Clean up for next iteration
            \App\Models\CalendarEvent::truncate();
        }
    }

    public function test_generator_with_complex_overrides_performance(): void
    {
        $complexOverrides = [
            'title' => str_repeat('Complex Event Title ', 10),
            'attendees' => array_fill(0, 20, ['name' => 'Test User', 'email' => 'test@example.com']),
            'notes' => str_repeat('This is a very long note. ', 50),
            'agenda' => str_repeat('Agenda item. ', 30),
        ];

        $startTime = microtime(true);

        $events = CalendarEventGenerator::generateMultiple(
            $this->team,
            25,
            $this->user,
            $complexOverrides,
        );

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertLessThan(3.0, $executionTime,
            'Generation with complex overrides should complete within 3s');
        $this->assertCount(25, $events);

        // Verify complex data was applied
        foreach ($events as $event) {
            $this->assertStringContainsString('Complex Event Title', $event->title);
            $this->assertCount(20, $event->attendees);
        }
    }

    /**
     * Benchmark test to establish performance baseline.
     * This test helps identify performance regressions over time.
     */
    public function test_performance_baseline_benchmark(): void
    {
        $benchmarks = [];

        // Single event generation
        $startTime = microtime(true);
        CalendarEventGenerator::generate($this->team, $this->user);
        $benchmarks['single_event'] = microtime(true) - $startTime;

        // Bulk generation (50 events)
        $startTime = microtime(true);
        CalendarEventGenerator::generateMultiple($this->team, 50, $this->user);
        $benchmarks['bulk_50'] = microtime(true) - $startTime;

        // Recurring event with instances
        $startTime = microtime(true);
        $parent = CalendarEventGenerator::generateRecurring($this->team, $this->user);
        for ($i = 1; $i <= 5; $i++) {
            CalendarEventGenerator::generateRecurringInstance(
                $parent,
                $parent->start_at->copy()->addWeeks($i),
            );
        }
        $benchmarks['recurring_with_instances'] = microtime(true) - $startTime;

        // Data generation without persistence
        $startTime = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            CalendarEventGenerator::generateData($this->team, $this->user);
        }
        $benchmarks['data_only_100'] = microtime(true) - $startTime;

        // Log benchmarks for monitoring (in real scenarios, you might send to monitoring service)
        foreach ($benchmarks as $time) {
            $this->addToAssertionCount(1); // Ensure test is counted
            // In production, you might log these to a monitoring service
            // Log::info("CalendarEventGenerator benchmark: {$test} = {$time}s");
        }

        // Basic assertions to ensure reasonable performance
        $this->assertLessThan(0.1, $benchmarks['single_event']);
        $this->assertLessThan(5.0, $benchmarks['bulk_50']);
        $this->assertLessThan(2.0, $benchmarks['recurring_with_instances']);
        $this->assertLessThan(1.0, $benchmarks['data_only_100']);
    }
}
