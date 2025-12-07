<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

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

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();
    $this->team->users()->attach($this->user);
    $this->engine = new ProcessEngine;
});

/**
 * Feature: advanced-features, Property 1: Process determinism
 *
 * Validates: Requirements 1.1, 1.2
 *
 * Property: Processes execute steps/approvals/escalations in defined order
 * with audit trails and version adherence.
 */
test('process executes steps in defined order', function () {
    $definition = ProcessDefinition::factory()
        ->for($this->team)
        ->withSimpleSteps()
        ->active()
        ->create();

    $execution = $this->engine->startExecution($definition, $this->user->id);

    expect($execution->status)->toBe(ProcessExecutionStatus::IN_PROGRESS)
        ->and($execution->process_version)->toBe($definition->version)
        ->and($execution->steps)->toHaveCount(2)
        ->and($execution->steps->first()->step_order)->toBe(1)
        ->and($execution->steps->last()->step_order)->toBe(2);

    // Verify audit log for execution start
    $auditLog = $execution->auditLogs()->where('event_type', ProcessEventType::EXECUTION_STARTED)->first();
    expect($auditLog)->not->toBeNull()
        ->and($auditLog->event_description)->toBe('Process execution started');
});

test('process steps execute in sequential order', function () {
    $definition = ProcessDefinition::factory()
        ->for($this->team)
        ->withSimpleSteps()
        ->active()
        ->create();

    $execution = $this->engine->startExecution($definition, $this->user->id);

    // Execute first step
    $step1 = $this->engine->executeNextStep($execution);
    expect($step1)->not->toBeNull()
        ->and($step1->step_order)->toBe(1)
        ->and($step1->status)->toBe(ProcessStepStatus::IN_PROGRESS);

    // Complete first step
    $this->engine->completeStep($execution->fresh(), $step1->fresh(), ['result' => 'success']);

    $step1 = $step1->fresh();
    expect($step1->status)->toBe(ProcessStepStatus::COMPLETED)
        ->and($step1->output_data)->toBe(['result' => 'success']);

    // Execute second step
    $step2 = $this->engine->executeNextStep($execution->fresh());
    expect($step2)->not->toBeNull()
        ->and($step2->step_order)->toBe(2)
        ->and($step2->status)->toBe(ProcessStepStatus::IN_PROGRESS);

    // Complete second step
    $this->engine->completeStep($execution->fresh(), $step2->fresh());

    // Verify execution is completed
    $execution = $execution->fresh();
    expect($execution->status)->toBe(ProcessExecutionStatus::COMPLETED)
        ->and($execution->completed_at)->not->toBeNull();
});

test('process maintains audit trail for all events', function () {
    $definition = ProcessDefinition::factory()
        ->for($this->team)
        ->withSimpleSteps()
        ->active()
        ->create();

    $execution = $this->engine->startExecution($definition, $this->user->id);

    // Execute and complete first step
    $step1 = $this->engine->executeNextStep($execution);
    $this->engine->completeStep($execution->fresh(), $step1->fresh());

    // Execute and complete second step
    $step2 = $this->engine->executeNextStep($execution->fresh());
    $this->engine->completeStep($execution->fresh(), $step2->fresh());

    $execution = $execution->fresh();

    // Verify audit trail contains all events
    $auditLogs = $execution->auditLogs()->orderBy('id')->get();

    expect($auditLogs->count())->toBeGreaterThanOrEqual(5);

    // Verify key events exist
    $eventTypes = $auditLogs->pluck('event_type')->toArray();
    expect($eventTypes)->toContain(ProcessEventType::EXECUTION_STARTED)
        ->and($eventTypes)->toContain(ProcessEventType::STEP_STARTED)
        ->and($eventTypes)->toContain(ProcessEventType::STEP_COMPLETED);
});

test('process adheres to version from definition', function () {
    $definition = ProcessDefinition::factory()
        ->for($this->team)
        ->withSimpleSteps()
        ->active()
        ->create(['version' => 5]);

    $execution = $this->engine->startExecution($definition, $this->user->id);

    expect($execution->process_version)->toBe(5)
        ->and($execution->process_definition_id)->toBe($definition->id);

    // Update definition version
    $definition->update(['version' => 6]);

    // Execution should still reference original version
    expect($execution->fresh()->process_version)->toBe(5);
});

test('process handles approval steps correctly', function () {
    $approver = User::factory()->create();
    $this->team->users()->attach($approver);

    $definition = ProcessDefinition::factory()
        ->for($this->team)
        ->active()
        ->create([
            'steps' => [
                [
                    'key' => 'step_1',
                    'name' => 'Approval Step',
                    'requires_approval' => true,
                    'approver_id' => $approver->id,
                ],
            ],
        ]);

    $execution = $this->engine->startExecution($definition, $this->user->id);

    // Execute approval step
    $step = $this->engine->executeNextStep($execution);

    expect($step->status)->toBe(ProcessStepStatus::IN_PROGRESS)
        ->and($execution->fresh()->status)->toBe(ProcessExecutionStatus::AWAITING_APPROVAL);

    // Verify approval was created
    $approval = $execution->approvals()->first();
    expect($approval)->not->toBeNull()
        ->and($approval->status)->toBe(ProcessApprovalStatus::PENDING)
        ->and($approval->approver_id)->toBe($approver->id);

    // Approve the step
    $this->engine->approveStep($approval, $approver->id, 'Looks good');

    $approval = $approval->fresh();
    expect($approval->status)->toBe(ProcessApprovalStatus::APPROVED)
        ->and($approval->decided_at)->not->toBeNull()
        ->and($approval->decision_notes)->toBe('Looks good');

    // Verify step is completed
    expect($step->fresh()->status)->toBe(ProcessStepStatus::COMPLETED);

    // Verify audit log
    $auditLog = $execution->auditLogs()
        ->where('event_type', ProcessEventType::APPROVAL_GRANTED)
        ->first();
    expect($auditLog)->not->toBeNull();
});

test('process handles approval rejection', function () {
    $approver = User::factory()->create();
    $this->team->users()->attach($approver);

    $definition = ProcessDefinition::factory()
        ->for($this->team)
        ->active()
        ->create([
            'steps' => [
                [
                    'key' => 'step_1',
                    'name' => 'Approval Step',
                    'requires_approval' => true,
                    'approver_id' => $approver->id,
                ],
            ],
        ]);

    $execution = $this->engine->startExecution($definition, $this->user->id);
    $step = $this->engine->executeNextStep($execution);
    $approval = $execution->approvals()->first();

    // Reject the step
    $this->engine->rejectStep($approval, $approver->id, 'Not acceptable');

    $approval = $approval->fresh();
    expect($approval->status)->toBe(ProcessApprovalStatus::REJECTED)
        ->and($approval->decision_notes)->toBe('Not acceptable');

    // Verify execution failed
    expect($execution->fresh()->status)->toBe(ProcessExecutionStatus::FAILED);

    // Verify audit log
    $auditLog = $execution->auditLogs()
        ->where('event_type', ProcessEventType::APPROVAL_REJECTED)
        ->first();
    expect($auditLog)->not->toBeNull();
});

test('process handles escalations', function () {
    $escalatedTo = User::factory()->create();
    $this->team->users()->attach($escalatedTo);

    $definition = ProcessDefinition::factory()
        ->for($this->team)
        ->withSimpleSteps()
        ->active()
        ->create();

    $execution = $this->engine->startExecution($definition, $this->user->id);
    $step = $this->engine->executeNextStep($execution);

    // Escalate the process
    $escalation = $this->engine->escalate(
        $execution->fresh(),
        $escalatedTo->id,
        $this->user->id,
        'SLA breach',
        $step,
        'Step taking too long'
    );

    expect($escalation)->not->toBeNull()
        ->and($escalation->escalation_reason)->toBe('SLA breach')
        ->and($escalation->escalated_to_id)->toBe($escalatedTo->id)
        ->and($escalation->is_resolved)->toBe(false);

    // Verify execution status
    expect($execution->fresh()->status)->toBe(ProcessExecutionStatus::ESCALATED);

    // Verify audit log
    $auditLog = $execution->auditLogs()
        ->where('event_type', ProcessEventType::ESCALATION_TRIGGERED)
        ->first();
    expect($auditLog)->not->toBeNull()
        ->and($auditLog->event_description)->toBe('Process escalated');
});

test('process handles rollback', function () {
    $definition = ProcessDefinition::factory()
        ->for($this->team)
        ->withSimpleSteps()
        ->active()
        ->create();

    $execution = $this->engine->startExecution($definition, $this->user->id);

    // Execute and complete first step
    $step = $this->engine->executeNextStep($execution);
    $this->engine->completeStep($execution->fresh(), $step->fresh());

    // Rollback the process
    $rollbackData = ['reason' => 'Data error', 'reverted_changes' => ['field' => 'old_value']];
    $this->engine->rollback($execution->fresh(), $this->user->id, $rollbackData);

    $execution = $execution->fresh();
    expect($execution->status)->toBe(ProcessExecutionStatus::ROLLED_BACK)
        ->and($execution->rollback_data)->toBe($rollbackData);

    // Verify audit log
    $auditLog = $execution->auditLogs()
        ->where('event_type', ProcessEventType::ROLLBACK_COMPLETED)
        ->first();
    expect($auditLog)->not->toBeNull();
});

test('process handles step failure', function () {
    $definition = ProcessDefinition::factory()
        ->for($this->team)
        ->withSimpleSteps()
        ->active()
        ->create();

    $execution = $this->engine->startExecution($definition, $this->user->id);
    $step = $this->engine->executeNextStep($execution);

    // Fail the step
    $this->engine->failStep($execution->fresh(), $step->fresh(), 'Validation error');

    $step = $step->fresh();
    expect($step->status)->toBe(ProcessStepStatus::FAILED)
        ->and($step->error_message)->toBe('Validation error');

    // Verify execution failed
    expect($execution->fresh()->status)->toBe(ProcessExecutionStatus::FAILED);

    // Verify audit log
    $auditLog = $execution->auditLogs()
        ->where('event_type', ProcessEventType::STEP_FAILED)
        ->first();
    expect($auditLog)->not->toBeNull();
});

test('process calculates SLA due dates', function () {
    $definition = ProcessDefinition::factory()
        ->for($this->team)
        ->withSimpleSteps()
        ->active()
        ->create([
            'sla_config' => ['hours' => 48],
        ]);

    $execution = $this->engine->startExecution($definition, $this->user->id);

    expect($execution->sla_due_at)->not->toBeNull();

    $expectedDueAt = now()->addHours(48);
    $actualDueAt = $execution->sla_due_at;

    // Allow 1 second tolerance for test execution time
    expect($actualDueAt->diffInSeconds($expectedDueAt, false))->toBeLessThanOrEqual(1);
});

test('process maintains execution state', function () {
    $definition = ProcessDefinition::factory()
        ->for($this->team)
        ->withSimpleSteps()
        ->active()
        ->create();

    $execution = $this->engine->startExecution($definition, $this->user->id);

    expect($execution->execution_state)->toBe(['current_step' => 0]);

    // Execute first step
    $step1 = $this->engine->executeNextStep($execution);

    expect($execution->fresh()->execution_state)->toBe(['current_step' => 1]);

    // Complete first step and execute second
    $this->engine->completeStep($execution->fresh(), $step1->fresh());
    $step2 = $this->engine->executeNextStep($execution->fresh());

    expect($execution->fresh()->execution_state)->toBe(['current_step' => 2]);
});

test('process completes when all steps are done', function () {
    $definition = ProcessDefinition::factory()
        ->for($this->team)
        ->withSimpleSteps()
        ->active()
        ->create();

    $execution = $this->engine->startExecution($definition, $this->user->id);

    // Execute and complete all steps
    $step1 = $this->engine->executeNextStep($execution);
    $this->engine->completeStep($execution->fresh(), $step1->fresh());

    $step2 = $this->engine->executeNextStep($execution->fresh());
    $this->engine->completeStep($execution->fresh(), $step2->fresh());

    // Try to execute next step (should be null)
    $nextStep = $this->engine->executeNextStep($execution->fresh());
    expect($nextStep)->toBeNull();

    // Verify execution is completed
    $execution = $execution->fresh();
    expect($execution->status)->toBe(ProcessExecutionStatus::COMPLETED)
        ->and($execution->completed_at)->not->toBeNull();

    // Verify completion audit log
    $auditLog = $execution->auditLogs()
        ->where('event_type', ProcessEventType::EXECUTION_COMPLETED)
        ->first();
    expect($auditLog)->not->toBeNull();
});
