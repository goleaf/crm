<?php

declare(strict_types=1);

namespace Tests\Feature\AdvancedFeatures;

use App\Enums\ProcessApprovalStatus;
use App\Enums\ProcessEventType;
use App\Enums\ProcessExecutionStatus;
use App\Enums\ProcessStepStatus;
use App\Models\ProcessDefinition;
use App\Models\Team;
use App\Models\User;
use App\Services\ProcessEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Integration test: Process execution with approvals and escalations
 *
 * Tests the complete workflow of a process with multiple steps,
 * approval requirements, and escalation handling.
 */
test('complete process execution with approvals and escalations', function () {
    $team = Team::factory()->create();
    $initiator = User::factory()->create();
    $approver = User::factory()->create();
    $escalatedTo = User::factory()->create();

    $team->users()->attach([$initiator->id, $approver->id, $escalatedTo->id]);

    $engine = new ProcessEngine;

    // Create a complex process definition
    $definition = ProcessDefinition::factory()
        ->for($team)
        ->active()
        ->create([
            'steps' => [
                [
                    'key' => 'step_1',
                    'name' => 'Initial Step',
                    'requires_approval' => false,
                ],
                [
                    'key' => 'step_2',
                    'name' => 'Approval Step',
                    'requires_approval' => true,
                    'approver_id' => $approver->id,
                ],
                [
                    'key' => 'step_3',
                    'name' => 'Final Step',
                    'requires_approval' => false,
                ],
            ],
            'sla_config' => ['hours' => 48],
        ]);

    // Start execution
    $execution = $engine->startExecution($definition, $initiator->id);

    expect($execution->status)->toBe(ProcessExecutionStatus::IN_PROGRESS)
        ->and($execution->sla_due_at)->not->toBeNull()
        ->and($execution->auditLogs()->count())->toBeGreaterThan(0);

    // Execute first step
    $step1 = $engine->executeNextStep($execution);
    expect($step1->step_order)->toBe(1)
        ->and($step1->status)->toBe(ProcessStepStatus::IN_PROGRESS);

    $engine->completeStep($execution->fresh(), $step1->fresh(), ['result' => 'success']);
    expect($step1->fresh()->status)->toBe(ProcessStepStatus::COMPLETED);

    // Execute approval step
    $step2 = $engine->executeNextStep($execution->fresh());
    expect($step2->step_order)->toBe(2)
        ->and($execution->fresh()->status)->toBe(ProcessExecutionStatus::AWAITING_APPROVAL);

    $approval = $execution->approvals()->first();
    expect($approval)->not->toBeNull()
        ->and($approval->status)->toBe(ProcessApprovalStatus::PENDING);

    // Escalate before approval
    $escalation = $engine->escalate(
        $execution->fresh(),
        $escalatedTo->id,
        $initiator->id,
        'SLA at risk',
        $step2,
        'Approval taking too long'
    );

    expect($escalation)->not->toBeNull()
        ->and($execution->fresh()->status)->toBe(ProcessExecutionStatus::ESCALATED);

    // Approve the step
    $engine->approveStep($approval->fresh(), $approver->id, 'Approved after escalation');

    expect($approval->fresh()->status)->toBe(ProcessApprovalStatus::APPROVED)
        ->and($step2->fresh()->status)->toBe(ProcessStepStatus::COMPLETED);

    // Execute final step
    $step3 = $engine->executeNextStep($execution->fresh());
    expect($step3->step_order)->toBe(3);

    $engine->completeStep($execution->fresh(), $step3->fresh());

    // Verify completion
    $execution = $execution->fresh();
    expect($execution->status)->toBe(ProcessExecutionStatus::COMPLETED)
        ->and($execution->completed_at)->not->toBeNull();

    // Verify audit trail completeness
    $auditLogs = $execution->auditLogs()->orderBy('id')->get();
    $eventTypes = $auditLogs->pluck('event_type')->toArray();

    expect($eventTypes)->toContain(ProcessEventType::EXECUTION_STARTED)
        ->and($eventTypes)->toContain(ProcessEventType::STEP_STARTED)
        ->and($eventTypes)->toContain(ProcessEventType::STEP_COMPLETED)
        ->and($eventTypes)->toContain(ProcessEventType::ESCALATION_TRIGGERED)
        ->and($eventTypes)->toContain(ProcessEventType::APPROVAL_GRANTED)
        ->and($eventTypes)->toContain(ProcessEventType::EXECUTION_COMPLETED);
});

/**
 * Integration test: Process rollback after partial execution
 */
test('process rollback after partial execution', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);

    $engine = new ProcessEngine;

    $definition = ProcessDefinition::factory()
        ->for($team)
        ->withSimpleSteps()
        ->active()
        ->create();

    $execution = $engine->startExecution($definition, $user->id);

    // Execute and complete first step
    $step1 = $engine->executeNextStep($execution);
    $engine->completeStep($execution->fresh(), $step1->fresh(), ['data' => 'value1']);

    // Execute second step
    $step2 = $engine->executeNextStep($execution->fresh());
    $engine->completeStep($execution->fresh(), $step2->fresh(), ['data' => 'value2']);

    // Rollback the entire process
    $rollbackData = [
        'reason' => 'Data integrity issue',
        'reverted_changes' => [
            'step_1' => ['data' => 'value1'],
            'step_2' => ['data' => 'value2'],
        ],
    ];

    $engine->rollback($execution->fresh(), $user->id, $rollbackData);

    $execution = $execution->fresh();
    expect($execution->status)->toBe(ProcessExecutionStatus::ROLLED_BACK)
        ->and($execution->rollback_data)->toBe($rollbackData);

    // Verify rollback audit log
    $rollbackLog = $execution->auditLogs()
        ->where('event_type', ProcessEventType::ROLLBACK_COMPLETED)
        ->first();

    expect($rollbackLog)->not->toBeNull()
        ->and($rollbackLog->event_data)->toHaveKey('rollback_data');
});

/**
 * Integration test: Process with approval rejection
 */
test('process fails when approval is rejected', function () {
    $team = Team::factory()->create();
    $initiator = User::factory()->create();
    $approver = User::factory()->create();
    $team->users()->attach([$initiator->id, $approver->id]);

    $engine = new ProcessEngine;

    $definition = ProcessDefinition::factory()
        ->for($team)
        ->active()
        ->create([
            'steps' => [
                [
                    'key' => 'approval_step',
                    'name' => 'Critical Approval',
                    'requires_approval' => true,
                    'approver_id' => $approver->id,
                ],
            ],
        ]);

    $execution = $engine->startExecution($definition, $initiator->id);
    $step = $engine->executeNextStep($execution);
    $approval = $execution->approvals()->first();

    // Reject the approval
    $engine->rejectStep($approval, $approver->id, 'Does not meet requirements');

    expect($approval->fresh()->status)->toBe(ProcessApprovalStatus::REJECTED)
        ->and($execution->fresh()->status)->toBe(ProcessExecutionStatus::FAILED);

    // Verify rejection audit log
    $rejectionLog = $execution->auditLogs()
        ->where('event_type', ProcessEventType::APPROVAL_REJECTED)
        ->first();

    expect($rejectionLog)->not->toBeNull();
});

/**
 * Integration test: Process version adherence across updates
 */
test('process execution maintains version consistency', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);

    $engine = new ProcessEngine;

    // Create initial version
    $definition = ProcessDefinition::factory()
        ->for($team)
        ->withSimpleSteps()
        ->active()
        ->create(['version' => 1]);

    // Start execution with version 1
    $execution1 = $engine->startExecution($definition, $user->id);
    expect($execution1->process_version)->toBe(1);

    // Update definition to version 2
    $definition->update(['version' => 2]);

    // Start new execution - should use version 2
    $execution2 = $engine->startExecution($definition->fresh(), $user->id);
    expect($execution2->process_version)->toBe(2);

    // Original execution should still reference version 1
    expect($execution1->fresh()->process_version)->toBe(1);

    // Complete both executions
    $step1 = $engine->executeNextStep($execution1->fresh());
    $engine->completeStep($execution1->fresh(), $step1->fresh());
    $step2 = $engine->executeNextStep($execution1->fresh());
    $engine->completeStep($execution1->fresh(), $step2->fresh());

    expect($execution1->fresh()->status)->toBe(ProcessExecutionStatus::COMPLETED)
        ->and($execution1->fresh()->process_version)->toBe(1);
});
