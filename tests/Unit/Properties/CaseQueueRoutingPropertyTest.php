<?php

declare(strict_types=1);

namespace Tests\Unit\Properties;

use App\Enums\CaseChannel;
use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\CaseType;
use App\Models\SupportCase;
use App\Models\Team;
use App\Services\CaseQueueRoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\Support\PropertyTestCase;

/**
 * Property 8: Case queue routing
 *
 * Email-to-case or portal submissions must land in the correct queue/team based on rules (status/type/priority).
 */
final class CaseQueueRoutingPropertyTest extends PropertyTestCase
{
    use RefreshDatabase;

    private CaseQueueRoutingService $queueRoutingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queueRoutingService = resolve(CaseQueueRoutingService::class);

        // Use the default configuration from config/cases.php
        // No need to override - the service should use the default config
    }

    public function test_queue_determination_by_priority_and_type_property(): void
    {
        $this->forAll(
            $this->generator->elements(CasePriority::cases()),
            $this->generator->elements(CaseType::cases()),
        )->then(function (CasePriority $priority, CaseType $type): void {
            // Given: A case with specific priority and type
            $case = SupportCase::factory()->create([
                'team_id' => $this->team->id,
                'priority' => $priority,
                'type' => $type,
                'status' => CaseStatus::NEW,
                'channel' => CaseChannel::INTERNAL,
            ]);

            // When: We determine the queue
            $queue = $this->queueRoutingService->determineQueue($case);

            // Then: The queue should be a valid queue name
            $this->assertIsString($queue);
            $this->assertNotEmpty($queue);

            // And: Should be one of the available queues
            $availableQueues = $this->queueRoutingService->getAvailableQueues();
            $this->assertContains($queue, $availableQueues);

            // And: Verify specific routing rules from config/cases.php
            if ($priority === CasePriority::P1) {
                // P1 priority always goes to critical queue (first rule)
                $this->assertEquals('critical', $queue);
            } elseif (in_array($type, [CaseType::INCIDENT, CaseType::PROBLEM], true)) {
                // Technical issues go to technical queue
                $this->assertEquals('technical', $queue);
            } elseif ($type === CaseType::REQUEST) {
                // Service requests go to service queue
                $this->assertEquals('service', $queue);
            } elseif ($type === CaseType::QUESTION) {
                // Questions go to general queue
                $this->assertEquals('general', $queue);
            }
        });
    }

    public function test_queue_determination_by_channel_property(): void
    {
        $this->forAll(
            $this->generator->elements(CaseChannel::cases()),
            $this->generator->elements(CasePriority::cases()),
        )->then(function (CaseChannel $channel, CasePriority $priority): void {
            // Given: A case with specific channel and priority
            $case = SupportCase::factory()->create([
                'team_id' => $this->team->id,
                'priority' => $priority,
                'type' => CaseType::QUESTION,
                'status' => CaseStatus::NEW,
                'channel' => $channel,
            ]);

            // When: We determine the queue
            $queue = $this->queueRoutingService->determineQueue($case);

            // Then: The queue should be a valid queue name
            $this->assertIsString($queue);
            $this->assertNotEmpty($queue);

            // And: Should be one of the available queues
            $availableQueues = $this->queueRoutingService->getAvailableQueues();
            $this->assertContains($queue, $availableQueues);

            // And: Verify routing rules (channel doesn't affect routing in default config)
            if ($priority === CasePriority::P1) {
                // P1 priority always goes to critical queue
                $this->assertEquals('critical', $queue);
            } elseif ($case->type === CaseType::QUESTION) {
                // Questions go to general queue regardless of channel
                $this->assertEquals('general', $queue);
            }
        });
    }

    public function test_team_assignment_property(): void
    {
        $this->forAll(
            $this->generator->elements(CasePriority::cases()),
            $this->generator->elements(CaseType::cases()),
        )->then(function (CasePriority $priority, CaseType $type): void {
            // Given: A case that matches team assignment rules
            $case = SupportCase::factory()->create([
                'team_id' => $this->team->id,
                'priority' => $priority,
                'type' => $type,
                'status' => CaseStatus::NEW,
                'channel' => CaseChannel::EMAIL,
                'assigned_team_id' => null,
            ]);

            // When: We determine the team
            $teamId = $this->queueRoutingService->determineTeam($case);

            // Then: Team assignment should follow the rules (default config has no team assignments)
            // The default configuration in config/cases.php sets team_id to null for all rules
            $this->assertNull($teamId);
        });
    }

    public function test_queue_assignment_execution_property(): void
    {
        $this->forAll(
            $this->generator->elements(CasePriority::cases()),
            $this->generator->elements(CaseChannel::cases()),
        )->then(function (CasePriority $priority, CaseChannel $channel): void {
            // Given: A case without queue assignment
            $case = SupportCase::factory()->create([
                'team_id' => $this->team->id,
                'priority' => $priority,
                'type' => CaseType::QUESTION,
                'status' => CaseStatus::NEW,
                'channel' => $channel,
                'queue' => null,
                'assigned_team_id' => null,
            ]);

            $originalQueue = $case->queue;
            $originalTeamId = $case->assigned_team_id;

            // When: We assign the queue
            $this->queueRoutingService->assignQueue($case);

            // Then: The case should be assigned to the correct queue and team
            $case->refresh();

            // Queue should be assigned
            $this->assertNotNull($case->queue);
            $this->assertNotEquals($originalQueue, $case->queue);

            // Verify the queue matches what determineQueue would return
            $expectedQueue = $this->queueRoutingService->determineQueue($case);
            $this->assertEquals($expectedQueue, $case->queue);

            // Team assignment should follow rules
            $expectedTeamId = $this->queueRoutingService->determineTeam($case);
            $this->assertEquals($expectedTeamId, $case->assigned_team_id);
        });
    }

    public function test_rule_matching_precedence_property(): void
    {
        // Given: A case that could match multiple rules
        $case = SupportCase::factory()->create([
            'team_id' => $this->team->id,
            'priority' => CasePriority::P1,
            'type' => CaseType::INCIDENT,
            'status' => CaseStatus::NEW,
            'channel' => CaseChannel::EMAIL,
        ]);

        // When: We determine the queue
        $queue = $this->queueRoutingService->determineQueue($case);

        // Then: The first matching rule should take precedence
        // P1 priority should match the first rule (critical queue)
        $this->assertEquals('critical', $queue);

        // And: Team should be null (default config has no team assignments)
        $teamId = $this->queueRoutingService->determineTeam($case);
        $this->assertNull($teamId);
    }

    public function test_disabled_routing_fallback_property(): void
    {
        // Given: Queue routing is disabled
        Config::set('cases.queue_routing.enabled', false);

        $this->forAll(
            $this->generator->elements(CasePriority::cases()),
            $this->generator->elements(CaseType::cases()),
            $this->generator->elements(CaseChannel::cases()),
        )->then(function (CasePriority $priority, CaseType $type, CaseChannel $channel): void {
            // Given: A case with any attributes
            $case = SupportCase::factory()->create([
                'team_id' => $this->team->id,
                'priority' => $priority,
                'type' => $type,
                'channel' => $channel,
                'status' => CaseStatus::NEW,
            ]);

            // When: We determine the queue
            $queue = $this->queueRoutingService->determineQueue($case);

            // Then: Should always use default queue
            $this->assertEquals('general', $queue);

            // And: No team should be assigned
            $teamId = $this->queueRoutingService->determineTeam($case);
            $this->assertNull($teamId);
        });
    }

    public function test_no_matching_rules_fallback_property(): void
    {
        $this->forAll(
            $this->generator->elements([CasePriority::P4]), // Use P4 which doesn't match critical rules
            $this->generator->elements([CaseType::REQUEST]), // Use type that doesn't match rules
            $this->generator->elements([CaseChannel::INTERNAL]), // Use channel that doesn't match rules
        )->then(function (CasePriority $priority, CaseType $type, CaseChannel $channel): void {
            // Given: A case that doesn't match any specific rules
            $case = SupportCase::factory()->create([
                'team_id' => $this->team->id,
                'priority' => $priority,
                'type' => $type,
                'channel' => $channel,
                'status' => CaseStatus::ASSIGNED, // Not 'new' to avoid standard queue rule
            ]);

            // When: We determine the queue
            $queue = $this->queueRoutingService->determineQueue($case);

            // Then: Should fall back to default queue
            $this->assertEquals('general', $queue);

            // And: No team should be assigned
            $teamId = $this->queueRoutingService->determineTeam($case);
            $this->assertNull($teamId);
        });
    }

    public function test_available_queues_property(): void
    {
        // When: We get available queues
        $queues = $this->queueRoutingService->getAvailableQueues();

        // Then: Should include all queues from default config
        $expectedQueues = ['critical', 'technical', 'service', 'general'];

        foreach ($expectedQueues as $expectedQueue) {
            $this->assertContains($expectedQueue, $queues);
        }

        // And: Should not contain duplicates
        $this->assertEquals(count($expectedQueues), count(array_unique($queues)));
    }

    public function test_enum_value_handling_property(): void
    {
        $this->forAll(
            $this->generator->elements(CasePriority::cases()),
            $this->generator->elements(CaseType::cases()),
        )->then(function (CasePriority $priority, CaseType $type): void {
            // Given: A case with enum values
            $case = SupportCase::factory()->create([
                'team_id' => $this->team->id,
                'priority' => $priority,
                'type' => $type,
                'status' => CaseStatus::NEW,
                'channel' => CaseChannel::EMAIL,
            ]);

            // When: We determine the queue (this tests internal enum handling)
            $queue = $this->queueRoutingService->determineQueue($case);

            // Then: Should handle enum values correctly without errors
            $this->assertIsString($queue);
            $this->assertNotEmpty($queue);

            // And: Should be one of the configured queues
            $availableQueues = $this->queueRoutingService->getAvailableQueues();
            $this->assertContains($queue, $availableQueues);
        });
    }
}
