<?php

declare(strict_types=1);

use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Models\SupportCase;
use App\Models\Team;
use App\Services\CaseEscalationService;
use App\Services\CaseSlaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/**
 * **Feature: core-crm-modules, Property 7: Case SLA enforcement**
 *
 * **Validates: Requirements 5.1**
 *
 * Property: Cases exceeding SLA thresholds must trigger escalation actions
 * and timestamp breaches.
 */

// Property: Cases past SLA due date are marked as breached
test('property: cases past sla due date are marked as breached', function (): void {
    $team = Team::factory()->create();
    $priority = fake()->randomElement(CasePriority::cases());

    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'priority' => $priority,
        'status' => CaseStatus::NEW,
        'sla_due_at' => now()->subMinutes(30), // Past due
        'sla_breached' => false,
        'resolved_at' => null,
    ]);

    $service = new CaseSlaService;
    $isBreached = $service->checkSlaBreach($case);

    expect($isBreached)->toBeTrue();

    $service->markSlaBreach($case);
    $case->refresh();

    expect($case->sla_breached)->toBeTrue()
        ->and($case->sla_breach_at)->not->toBeNull()
        ->and($case->sla_breach_at)->toBeInstanceOf(Carbon::class);
})->repeat(100);

// Property: Resolved cases are never marked as breached
test('property: resolved cases are never marked as breached', function (): void {
    $team = Team::factory()->create();
    $priority = fake()->randomElement(CasePriority::cases());

    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'priority' => $priority,
        'status' => CaseStatus::CLOSED,
        'sla_due_at' => now()->subMinutes(30), // Past due
        'resolved_at' => now()->subMinutes(10),
        'sla_breached' => false,
    ]);

    $service = new CaseSlaService;
    $isBreached = $service->checkSlaBreach($case);

    expect($isBreached)->toBeFalse();
})->repeat(100);

// Property: SLA due date is calculated based on priority
test('property: sla due date is calculated correctly based on priority', function (): void {
    $team = Team::factory()->create();
    $priority = fake()->randomElement(CasePriority::cases());

    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'priority' => $priority,
        'status' => CaseStatus::NEW,
        'sla_due_at' => null,
    ]);

    $service = new CaseSlaService;
    $slaDueDate = $service->calculateSlaDueDate($case);

    expect($slaDueDate)->not->toBeNull()
        ->and($slaDueDate)->toBeInstanceOf(Carbon::class)
        ->and($slaDueDate->isFuture())->toBeTrue();

    // Verify the SLA due date matches the configured resolution time
    $expectedMinutes = config("cases.sla.resolution_time.{$priority->value}");
    $actualMinutes = now()->diffInMinutes($slaDueDate);

    expect($actualMinutes)->toBeGreaterThanOrEqual($expectedMinutes - 1)
        ->and($actualMinutes)->toBeLessThanOrEqual($expectedMinutes + 1);
})->repeat(100);

// Property: First response time is recorded only once
test('property: first response time is recorded only once', function (): void {
    $team = Team::factory()->create();

    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'status' => CaseStatus::NEW,
        'first_response_at' => null,
        'response_time_minutes' => null,
    ]);

    $service = new CaseSlaService;

    // Record first response
    $service->recordFirstResponse($case);
    $case->refresh();

    $firstResponseAt = $case->first_response_at;
    $firstResponseTime = $case->response_time_minutes;

    expect($firstResponseAt)->not->toBeNull()
        ->and($firstResponseTime)->not->toBeNull()
        ->and($firstResponseTime)->toBeGreaterThanOrEqual(0);

    // Try to record again - should not change
    \Illuminate\Support\Sleep::sleep(1);
    $service->recordFirstResponse($case);
    $case->refresh();

    expect($case->first_response_at->timestamp)->toBe($firstResponseAt->timestamp)
        ->and($case->response_time_minutes)->toBe($firstResponseTime);
})->repeat(50);

// Property: Resolution time is recorded only once
test('property: resolution time is recorded only once and closes case', function (): void {
    $team = Team::factory()->create();

    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'status' => CaseStatus::ASSIGNED,
        'resolved_at' => null,
        'resolution_time_minutes' => null,
    ]);

    $service = new CaseSlaService;

    // Record resolution
    $service->recordResolution($case);
    $case->refresh();

    $resolvedAt = $case->resolved_at;
    $resolutionTime = $case->resolution_time_minutes;

    expect($resolvedAt)->not->toBeNull()
        ->and($resolutionTime)->not->toBeNull()
        ->and($resolutionTime)->toBeGreaterThanOrEqual(0)
        ->and($case->status)->toBe(CaseStatus::CLOSED);

    // Try to record again - should not change
    \Illuminate\Support\Sleep::sleep(1);
    $service->recordResolution($case);
    $case->refresh();

    expect($case->resolved_at->timestamp)->toBe($resolvedAt->timestamp)
        ->and($case->resolution_time_minutes)->toBe($resolutionTime);
})->repeat(50);

// Property: SLA breach processing identifies all breached cases
test('property: sla breach processing identifies all breached cases', function (): void {
    $team = Team::factory()->create();

    // Create cases with various SLA states
    $breachedCount = fake()->numberBetween(2, 5);
    $nonBreachedCount = fake()->numberBetween(2, 5);

    // Create breached cases (past due, not resolved, not yet marked)
    for ($i = 0; $i < $breachedCount; $i++) {
        SupportCase::factory()->create([
            'team_id' => $team->id,
            'sla_due_at' => now()->subMinutes(fake()->numberBetween(10, 100)),
            'sla_breached' => false,
            'resolved_at' => null,
        ]);
    }

    // Create non-breached cases (future due date)
    for ($i = 0; $i < $nonBreachedCount; $i++) {
        SupportCase::factory()->create([
            'team_id' => $team->id,
            'sla_due_at' => now()->addMinutes(fake()->numberBetween(10, 100)),
            'sla_breached' => false,
            'resolved_at' => null,
        ]);
    }

    $service = new CaseSlaService;
    $processedCount = $service->processSlaBreach();

    expect($processedCount)->toBe($breachedCount);

    // Verify all breached cases are marked
    $markedBreached = SupportCase::where('sla_breached', true)->count();
    expect($markedBreached)->toBe($breachedCount);
})->repeat(50);

// Property: Escalation occurs after breach threshold is exceeded
test('property: escalation occurs after breach threshold is exceeded', function (): void {
    $team = Team::factory()->create();

    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'sla_breached' => true,
        'sla_breach_at' => now()->subMinutes(35), // Breached 35 minutes ago
        'escalation_level' => 0,
        'resolved_at' => null,
    ]);

    $escalationService = new CaseEscalationService;

    // Should escalate (threshold is 30 minutes for level 1)
    $shouldEscalate = $escalationService->shouldEscalate($case);
    expect($shouldEscalate)->toBeTrue();

    $escalationService->escalate($case);
    $case->refresh();

    expect($case->escalation_level)->toBe(1)
        ->and($case->escalated_at)->not->toBeNull();
})->repeat(100);

// Property: Escalation does not occur before threshold
test('property: escalation does not occur before threshold is reached', function (): void {
    $team = Team::factory()->create();

    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'sla_breached' => true,
        'sla_breach_at' => now()->subMinutes(15), // Breached only 15 minutes ago
        'escalation_level' => 0,
        'resolved_at' => null,
    ]);

    $escalationService = new CaseEscalationService;

    // Should not escalate yet (threshold is 30 minutes for level 1)
    $shouldEscalate = $escalationService->shouldEscalate($case);
    expect($shouldEscalate)->toBeFalse();
})->repeat(100);

// Property: Escalation levels increase sequentially
test('property: escalation levels increase sequentially', function (): void {
    $team = Team::factory()->create();

    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'sla_breached' => true,
        'sla_breach_at' => now()->subMinutes(150), // Breached long ago
        'escalation_level' => 0,
        'resolved_at' => null,
    ]);

    $escalationService = new CaseEscalationService;

    // First escalation
    $escalationService->escalate($case);
    $case->refresh();
    expect($case->escalation_level)->toBe(1);

    // Update breach time to trigger next escalation
    $case->update(['sla_breach_at' => now()->subMinutes(150)]);

    // Second escalation
    if ($escalationService->shouldEscalate($case)) {
        $escalationService->escalate($case);
        $case->refresh();
        expect($case->escalation_level)->toBe(2);
    }
})->repeat(50);

// Property: Resolved cases do not escalate
test('property: resolved cases do not escalate regardless of breach duration', function (): void {
    $team = Team::factory()->create();

    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'sla_breached' => true,
        'sla_breach_at' => now()->subMinutes(200), // Breached long ago
        'escalation_level' => 0,
        'resolved_at' => now()->subMinutes(10), // But resolved
    ]);

    $escalationService = new CaseEscalationService;

    $shouldEscalate = $escalationService->shouldEscalate($case);
    expect($shouldEscalate)->toBeFalse();
})->repeat(100);

// Property: SLA due date updates when priority changes
test('property: sla due date updates when priority changes', function (): void {
    $team = Team::factory()->create();

    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'priority' => CasePriority::P3,
        'status' => CaseStatus::NEW,
        'resolved_at' => null,
    ]);

    $service = new CaseSlaService;

    // Set initial SLA
    $initialSla = $service->calculateSlaDueDate($case);
    $case->update(['sla_due_at' => $initialSla]);

    // Change priority to P1 (more urgent)
    $case->update(['priority' => CasePriority::P1]);
    $service->updateSlaDueDate($case);
    $case->refresh();

    $newSla = $case->sla_due_at;

    // New SLA should be sooner than initial SLA for higher priority
    expect($newSla)->not->toBeNull()
        ->and($newSla->isBefore($initialSla))->toBeTrue();
})->repeat(50);

// Property: Cases without SLA due date are never breached
test('property: cases without sla due date are never marked as breached', function (): void {
    $team = Team::factory()->create();

    $case = SupportCase::factory()->create([
        'team_id' => $team->id,
        'sla_due_at' => null,
        'sla_breached' => false,
        'resolved_at' => null,
    ]);

    $service = new CaseSlaService;
    $isBreached = $service->checkSlaBreach($case);

    expect($isBreached)->toBeFalse();
})->repeat(100);

// Property: Bulk SLA processing is idempotent
test('property: processing sla breaches multiple times does not duplicate marks', function (): void {
    $team = Team::factory()->create();

    // Create breached cases
    $caseCount = fake()->numberBetween(3, 7);
    for ($i = 0; $i < $caseCount; $i++) {
        SupportCase::factory()->create([
            'team_id' => $team->id,
            'sla_due_at' => now()->subMinutes(fake()->numberBetween(10, 100)),
            'sla_breached' => false,
            'resolved_at' => null,
        ]);
    }

    $service = new CaseSlaService;

    // First processing
    $firstCount = $service->processSlaBreach();
    expect($firstCount)->toBe($caseCount);

    // Second processing should find nothing new
    $secondCount = $service->processSlaBreach();
    expect($secondCount)->toBe(0);

    // Total breached should still be the original count
    $totalBreached = SupportCase::where('sla_breached', true)->count();
    expect($totalBreached)->toBe($caseCount);
})->repeat(50);
