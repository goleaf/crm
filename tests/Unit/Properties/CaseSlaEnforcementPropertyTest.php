<?php

declare(strict_types=1);

namespace Tests\Unit\Properties;

use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Models\SupportCase;
use App\Services\CaseEscalationService;
use App\Services\CaseSlaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Tests\Support\PropertyTestCase;

/**
 * Property 7: Case SLA enforcement
 *
 * Cases exceeding SLA thresholds must trigger escalation actions and timestamp breaches.
 */
final class CaseSlaEnforcementPropertyTest extends PropertyTestCase
{
    use RefreshDatabase;

    private CaseSlaService $slaService;

    private CaseEscalationService $escalationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->slaService = resolve(CaseSlaService::class);
        $this->escalationService = resolve(CaseEscalationService::class);

        // Set up SLA configuration for testing
        Config::set('cases.sla.resolution_time', [
            'p1' => 60,    // 1 hour for P1
            'p2' => 240,   // 4 hours for P2
            'p3' => 480,   // 8 hours for P3
            'p4' => 1440,  // 24 hours for P4
        ]);

        Config::set('cases.escalation.enabled', true);
        Config::set('cases.escalation.levels', [
            1 => ['threshold_minutes' => 30],
            2 => ['threshold_minutes' => 60],
            3 => ['threshold_minutes' => 120],
        ]);
    }

    public function test_sla_due_date_calculation_property(): void
    {
        $this->forAll(
            $this->generator->elements(CasePriority::cases()),
        )->then(function (CasePriority $priority): void {
            // Given: A case with a specific priority
            $case = SupportCase::factory()->create([
                'team_id' => $this->team->id,
                'priority' => $priority,
                'status' => CaseStatus::NEW,
                'resolved_at' => null,
            ]);

            // When: We calculate the SLA due date
            $slaDueDate = $this->slaService->calculateSlaDueDate($case);

            // Then: The SLA due date should be set according to priority configuration
            $expectedMinutes = Config::get("cases.sla.resolution_time.{$priority->value}");

            if ($expectedMinutes !== null) {
                $this->assertInstanceOf(Carbon::class, $slaDueDate);
                $this->assertEqualsWithDelta(
                    now()->addMinutes($expectedMinutes)->timestamp,
                    $slaDueDate->timestamp,
                    60, // Allow 1 minute tolerance for test execution time
                );
            } else {
                $this->assertNull($slaDueDate);
            }
        });
    }

    public function test_sla_breach_detection_property(): void
    {
        $this->forAll(
            $this->generator->elements(CasePriority::cases()),
            $this->generator->integers(-120, 120), // Minutes offset from now
        )->then(function (CasePriority $priority, int $minutesOffset): void {
            // Given: A case with SLA due date in the past or future
            $slaDueAt = now()->addMinutes($minutesOffset);

            $case = SupportCase::factory()->create([
                'team_id' => $this->team->id,
                'priority' => $priority,
                'status' => CaseStatus::NEW,
                'sla_due_at' => $slaDueAt,
                'resolved_at' => null,
                'sla_breached' => false,
            ]);

            // When: We check for SLA breach
            $isBreached = $this->slaService->checkSlaBreach($case);

            // Then: Breach detection should match whether due date is in the past or now
            $expectedBreach = $minutesOffset <= 0; // Negative or zero offset means past due or due now
            $this->assertEquals($expectedBreach, $isBreached);
        });
    }

    public function test_sla_breach_marking_property(): void
    {
        $this->forAll(
            $this->generator->elements(CasePriority::cases()),
        )->then(function (CasePriority $priority): void {
            // Given: A case that has breached SLA
            $case = SupportCase::factory()->create([
                'team_id' => $this->team->id,
                'priority' => $priority,
                'status' => CaseStatus::NEW,
                'sla_due_at' => now()->subHour(),
                'resolved_at' => null,
                'sla_breached' => false,
                'sla_breach_at' => null,
            ]);

            // When: We mark the SLA breach
            $this->slaService->markSlaBreach($case);

            // Then: The case should be marked as breached with timestamp
            $case->refresh();
            $this->assertTrue($case->sla_breached);
            $this->assertNotNull($case->sla_breach_at);
            $this->assertEqualsWithDelta(
                now()->timestamp,
                $case->sla_breach_at->timestamp,
                60, // Allow 1 minute tolerance
            );
        });
    }

    public function test_escalation_trigger_property(): void
    {
        $this->forAll(
            $this->generator->integers(0, 3), // Escalation level
            $this->generator->integers(0, 180), // Minutes since breach
        )->then(function (int $currentLevel, int $minutesSinceBreach): void {
            // Given: A case with SLA breach at specific time
            $breachTime = now()->subMinutes($minutesSinceBreach);

            $case = SupportCase::factory()->create([
                'team_id' => $this->team->id,
                'status' => CaseStatus::NEW,
                'sla_breached' => true,
                'sla_breach_at' => $breachTime,
                'escalation_level' => $currentLevel,
                'resolved_at' => null,
            ]);

            // When: We check if escalation should occur
            $shouldEscalate = $this->escalationService->shouldEscalate($case);

            // Then: Escalation should occur based on configured thresholds
            $nextLevel = $currentLevel + 1;
            $escalationLevels = Config::get('cases.escalation.levels', []);

            if (isset($escalationLevels[$nextLevel])) {
                $threshold = $escalationLevels[$nextLevel]['threshold_minutes'] ?? 0;
                $expectedEscalation = $minutesSinceBreach >= $threshold;
                $this->assertEquals($expectedEscalation, $shouldEscalate);
            } else {
                // No more escalation levels available
                $this->assertFalse($shouldEscalate);
            }
        });
    }

    public function test_escalation_execution_property(): void
    {
        $this->forAll(
            $this->generator->integers(0, 2), // Current escalation level (0-2, so next can be 1-3)
        )->then(function (int $currentLevel): void {
            // Given: A case that should be escalated
            $case = SupportCase::factory()->create([
                'team_id' => $this->team->id,
                'status' => CaseStatus::NEW,
                'sla_breached' => true,
                'sla_breach_at' => now()->subHours(2), // Well past any threshold
                'escalation_level' => $currentLevel,
                'escalated_at' => null,
                'resolved_at' => null,
            ]);

            $originalLevel = $case->escalation_level;

            // When: We escalate the case
            if ($this->escalationService->shouldEscalate($case)) {
                $this->escalationService->escalate($case);

                // Then: The escalation level should increase and timestamp should be set
                $case->refresh();
                $this->assertEquals($originalLevel + 1, $case->escalation_level);
                $this->assertNotNull($case->escalated_at);
                $this->assertEqualsWithDelta(
                    now()->timestamp,
                    $case->escalated_at->timestamp,
                    60, // Allow 1 minute tolerance
                );
            }
        });
    }

    public function test_resolved_cases_ignore_sla_property(): void
    {
        $this->forAll(
            $this->generator->elements(CasePriority::cases()),
            $this->generator->integers(-120, 120), // Minutes offset for SLA due date
        )->then(function (CasePriority $priority, int $minutesOffset): void {
            // Given: A resolved case with SLA due date in the past
            $case = SupportCase::factory()->create([
                'team_id' => $this->team->id,
                'priority' => $priority,
                'status' => CaseStatus::CLOSED,
                'sla_due_at' => now()->addMinutes($minutesOffset),
                'resolved_at' => now()->subMinutes(30),
                'sla_breached' => false,
            ]);

            // When: We check for SLA breach
            $isBreached = $this->slaService->checkSlaBreach($case);

            // Then: Resolved cases should never be considered breached
            $this->assertFalse($isBreached);

            // And: Escalation should not occur for resolved cases
            $shouldEscalate = $this->escalationService->shouldEscalate($case);
            $this->assertFalse($shouldEscalate);
        });
    }

    public function test_sla_processing_batch_property(): void
    {
        // Given: Multiple cases with different SLA states
        $breachedCases = collect();
        $nonBreachedCases = collect();

        for ($i = 0; $i < 5; $i++) {
            // Create breached cases
            $breachedCase = SupportCase::factory()->create([
                'team_id' => $this->team->id,
                'status' => CaseStatus::NEW,
                'sla_due_at' => now()->subHours($i + 1),
                'resolved_at' => null,
                'sla_breached' => false,
            ]);
            $breachedCases->push($breachedCase);

            // Create non-breached cases
            $nonBreachedCase = SupportCase::factory()->create([
                'team_id' => $this->team->id,
                'status' => CaseStatus::NEW,
                'sla_due_at' => now()->addHours($i + 1),
                'resolved_at' => null,
                'sla_breached' => false,
            ]);
            $nonBreachedCases->push($nonBreachedCase);
        }

        // When: We process SLA breaches
        $processedCount = $this->slaService->processSlaBreach();

        // Then: Only the breached cases should be marked as breached
        $this->assertEquals($breachedCases->count(), $processedCount);

        foreach ($breachedCases as $case) {
            $case->refresh();
            $this->assertTrue($case->sla_breached);
            $this->assertNotNull($case->sla_breach_at);
        }

        foreach ($nonBreachedCases as $case) {
            $case->refresh();
            $this->assertFalse($case->sla_breached);
            $this->assertNull($case->sla_breach_at);
        }
    }
}
