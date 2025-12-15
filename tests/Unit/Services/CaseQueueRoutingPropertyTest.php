<?php

declare(strict_types=1);

use App\Enums\CaseChannel;
use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\CaseType;
use App\Models\SupportCase;
use App\Models\Team;
use App\Services\CaseQueueRoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * **Feature: core-crm-modules, Property 8: Case queue routing**
 *
 * **Validates: Requirements 5.2, 5.3**
 *
 * Property: Email-to-case or portal submissions must land in the correct
 * queue/team based on rules (status/type/priority).
 */

// Property: Critical priority cases are routed to critical queue
test('property: critical priority cases are routed to critical queue', function (): void {
    $team = Team::factory()->create();

    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'priority' => CasePriority::P1,
        'type' => fake()->randomElement(CaseType::cases()),
        'status' => CaseStatus::NEW,
        'queue' => null,
    ]);

    $service = new CaseQueueRoutingService;
    $queue = $service->determineQueue($case);

    expect($queue)->toBe('critical');
})->repeat(100);

// Property: Technical issue types are routed to technical queue
test('property: technical issue types are routed to technical queue', function (): void {
    $team = Team::factory()->create();
    $technicalType = fake()->randomElement([CaseType::INCIDENT, CaseType::PROBLEM]);

    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'priority' => fake()->randomElement([CasePriority::P2, CasePriority::P3, CasePriority::P4]),
        'type' => $technicalType,
        'status' => CaseStatus::NEW,
        'queue' => null,
    ]);

    $service = new CaseQueueRoutingService;
    $queue = $service->determineQueue($case);

    expect($queue)->toBe('technical');
})->repeat(100);

// Property: Service requests are routed to service queue
test('property: service requests are routed to service queue', function (): void {
    $team = Team::factory()->create();

    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'priority' => fake()->randomElement([CasePriority::P2, CasePriority::P3, CasePriority::P4]),
        'type' => CaseType::REQUEST,
        'status' => CaseStatus::NEW,
        'queue' => null,
    ]);

    $service = new CaseQueueRoutingService;
    $queue = $service->determineQueue($case);

    expect($queue)->toBe('service');
})->repeat(100);

// Property: Questions are routed to general queue
test('property: questions are routed to general queue', function (): void {
    $team = Team::factory()->create();

    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'priority' => fake()->randomElement([CasePriority::P2, CasePriority::P3, CasePriority::P4]),
        'type' => CaseType::QUESTION,
        'status' => CaseStatus::NEW,
        'queue' => null,
    ]);

    $service = new CaseQueueRoutingService;
    $queue = $service->determineQueue($case);

    expect($queue)->toBe('general');
})->repeat(100);

// Property: Priority takes precedence over type in routing
test('property: critical priority overrides type-based routing', function (): void {
    $team = Team::factory()->create();

    // Even if it's a question, P1 priority should route to critical
    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'priority' => CasePriority::P1,
        'type' => CaseType::QUESTION,
        'status' => CaseStatus::NEW,
        'queue' => null,
    ]);

    $service = new CaseQueueRoutingService;
    $queue = $service->determineQueue($case);

    // Critical priority rule should match first
    expect($queue)->toBe('critical');
})->repeat(100);

// Property: Queue assignment updates case queue field
test('property: queue assignment updates case queue field', function (): void {
    $team = Team::factory()->create();

    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'priority' => fake()->randomElement(CasePriority::cases()),
        'type' => fake()->randomElement(CaseType::cases()),
        'status' => CaseStatus::NEW,
        'queue' => null,
    ]);

    $service = new CaseQueueRoutingService;
    $service->assignQueue($case);
    $case->refresh();

    expect($case->queue)->not->toBeNull()
        ->and($case->queue)->toBeString();
})->repeat(100);

// Property: All cases get assigned to a queue
test('property: all cases get assigned to a queue even without matching rules', function (): void {
    $team = Team::factory()->create();

    // Create a case that might not match specific rules
    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'priority' => fake()->randomElement(CasePriority::cases()),
        'type' => fake()->randomElement(CaseType::cases()),
        'status' => CaseStatus::NEW,
        'queue' => null,
    ]);

    $service = new CaseQueueRoutingService;
    $queue = $service->determineQueue($case);

    // Should always return a queue (default if no rules match)
    expect($queue)->not->toBeNull()
        ->and($queue)->toBeString()
        ->and($queue)->not->toBeEmpty();
})->repeat(100);

// Property: Queue routing is deterministic for same attributes
test('property: queue routing is deterministic for same case attributes', function (): void {
    $team = Team::factory()->create();
    $priority = fake()->randomElement(CasePriority::cases());
    $type = fake()->randomElement(CaseType::cases());

    $case1 = SupportCase::factory()->create([
        'team_id' => $team->id,
        'priority' => $priority,
        'type' => $type,
        'status' => CaseStatus::NEW,
        'queue' => null,
    ]);

    $case2 = SupportCase::factory()->create([
        'team_id' => $team->id,
        'priority' => $priority,
        'type' => $type,
        'status' => CaseStatus::NEW,
        'queue' => null,
    ]);

    $service = new CaseQueueRoutingService;
    $queue1 = $service->determineQueue($case1);
    $queue2 = $service->determineQueue($case2);

    // Same attributes should result in same queue
    expect($queue1)->toBe($queue2);
})->repeat(100);

// Property: Available queues list includes all configured queues
test('property: available queues includes all configured queues', function (): void {
    $service = new CaseQueueRoutingService;
    $queues = $service->getAvailableQueues();

    expect($queues)->toBeArray()
        ->and($queues)->not->toBeEmpty()
        ->and($queues)->toContain('critical')
        ->and($queues)->toContain('technical')
        ->and($queues)->toContain('service')
        ->and($queues)->toContain('general');
})->repeat(10);

// Property: Email channel cases are routed correctly
test('property: email channel cases are routed based on priority and type', function (): void {
    $team = Team::factory()->create();
    $priority = fake()->randomElement(CasePriority::cases());
    $type = fake()->randomElement(CaseType::cases());

    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'priority' => $priority,
        'type' => $type,
        'channel' => CaseChannel::EMAIL,
        'status' => CaseStatus::NEW,
        'queue' => null,
    ]);

    $service = new CaseQueueRoutingService;
    $queue = $service->determineQueue($case);

    // Should route based on priority/type, not channel
    expect($queue)->not->toBeNull();

    if ($priority === CasePriority::P1) {
        expect($queue)->toBe('critical');
    } elseif (in_array($type, [CaseType::INCIDENT, CaseType::PROBLEM])) {
        expect($queue)->toBe('technical');
    } elseif ($type === CaseType::REQUEST) {
        expect($queue)->toBe('service');
    } else {
        expect($queue)->toBe('general');
    }
})->repeat(100);

// Property: Portal channel cases are routed correctly
test('property: portal channel cases are routed based on priority and type', function (): void {
    $team = Team::factory()->create();
    $priority = fake()->randomElement(CasePriority::cases());
    $type = fake()->randomElement(CaseType::cases());

    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'priority' => $priority,
        'type' => $type,
        'channel' => CaseChannel::PORTAL,
        'status' => CaseStatus::NEW,
        'queue' => null,
    ]);

    $service = new CaseQueueRoutingService;
    $queue = $service->determineQueue($case);

    // Should route based on priority/type, not channel
    expect($queue)->not->toBeNull();

    if ($priority === CasePriority::P1) {
        expect($queue)->toBe('critical');
    } elseif (in_array($type, [CaseType::INCIDENT, CaseType::PROBLEM])) {
        expect($queue)->toBe('technical');
    } elseif ($type === CaseType::REQUEST) {
        expect($queue)->toBe('service');
    } else {
        expect($queue)->toBe('general');
    }
})->repeat(100);

// Property: Queue assignment is idempotent
test('property: assigning queue multiple times results in same queue', function (): void {
    $team = Team::factory()->create();

    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'priority' => fake()->randomElement(CasePriority::cases()),
        'type' => fake()->randomElement(CaseType::cases()),
        'status' => CaseStatus::NEW,
        'queue' => null,
    ]);

    $service = new CaseQueueRoutingService;

    // First assignment
    $service->assignQueue($case);
    $case->refresh();
    $firstQueue = $case->queue;

    // Second assignment (without changing attributes)
    $service->assignQueue($case);
    $case->refresh();
    $secondQueue = $case->queue;

    expect($firstQueue)->toBe($secondQueue);
})->repeat(50);

// Property: Changing priority updates queue assignment
test('property: changing priority can change queue assignment', function (): void {
    $team = Team::factory()->create();

    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'priority' => CasePriority::P3,
        'type' => CaseType::QUESTION,
        'status' => CaseStatus::NEW,
        'queue' => null,
    ]);

    $service = new CaseQueueRoutingService;

    // Initial assignment
    $service->assignQueue($case);
    $case->refresh();
    $initialQueue = $case->queue;

    // Change to critical priority
    $case->update(['priority' => CasePriority::P1]);
    $service->assignQueue($case);
    $case->refresh();
    $newQueue = $case->queue;

    // Should now be in critical queue
    expect($newQueue)->toBe('critical')
        ->and($newQueue)->not->toBe($initialQueue);
})->repeat(50);

// Property: Changing type updates queue assignment
test('property: changing type can change queue assignment', function (): void {
    $team = Team::factory()->create();

    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'priority' => CasePriority::P3,
        'type' => CaseType::QUESTION,
        'status' => CaseStatus::NEW,
        'queue' => null,
    ]);

    $service = new CaseQueueRoutingService;

    // Initial assignment
    $service->assignQueue($case);
    $case->refresh();
    $initialQueue = $case->queue;
    expect($initialQueue)->toBe('general');

    // Change to incident type
    $case->update(['type' => CaseType::INCIDENT]);
    $service->assignQueue($case);
    $case->refresh();
    $newQueue = $case->queue;

    // Should now be in technical queue
    expect($newQueue)->toBe('technical')
        ->and($newQueue)->not->toBe($initialQueue);
})->repeat(50);

// Property: Bulk routing maintains consistency
test('property: bulk routing assigns queues consistently', function (): void {
    $team = Team::factory()->create();

    // Create multiple cases with same attributes
    $priority = fake()->randomElement(CasePriority::cases());
    $type = fake()->randomElement(CaseType::cases());
    $caseCount = fake()->numberBetween(5, 10);

    $cases = [];
    for ($i = 0; $i < $caseCount; $i++) {
        $cases[] = SupportCase::factory()->create([
            'team_id' => $team->id,
            'priority' => $priority,
            'type' => $type,
            'status' => CaseStatus::NEW,
            'queue' => null,
        ]);
    }

    $service = new CaseQueueRoutingService;

    // Assign queues to all cases
    $queues = [];
    foreach ($cases as $case) {
        $service->assignQueue($case);
        $case->refresh();
        $queues[] = $case->queue;
    }

    // All should have the same queue
    $uniqueQueues = array_unique($queues);
    expect($uniqueQueues)->toHaveCount(1);
})->repeat(50);
