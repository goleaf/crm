<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ProcessApprovalStatus;
use App\Enums\ProcessEventType;
use App\Enums\ProcessExecutionStatus;
use App\Enums\ProcessStepStatus;
use App\Models\ProcessApproval;
use App\Models\ProcessAuditLog;
use App\Models\ProcessDefinition;
use App\Models\ProcessEscalation;
use App\Models\ProcessExecution;
use App\Models\ProcessExecutionStep;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class ProcessEngine
{
    /**
     * Start a new process execution.
     *
     * @param array<string, mixed> $contextData
     */
    public function startExecution(
        ProcessDefinition $definition,
        int $userId,
        array $contextData = [],
    ): ProcessExecution {
        return DB::transaction(function () use ($definition, $userId, $contextData) {
            $execution = ProcessExecution::create([
                'team_id' => $definition->team_id,
                'process_definition_id' => $definition->id,
                'initiated_by_id' => $userId,
                'status' => ProcessExecutionStatus::IN_PROGRESS,
                'process_version' => $definition->version,
                'context_data' => $contextData,
                'execution_state' => ['current_step' => 0],
                'started_at' => now(),
                'sla_due_at' => $this->calculateSlaDueDate($definition),
            ]);

            $this->createExecutionSteps($execution, $definition);

            $this->logAuditEvent(
                $execution,
                null,
                $userId,
                ProcessEventType::EXECUTION_STARTED,
                'Process execution started',
                ['context_data' => $contextData],
            );

            return $execution;
        });
    }

    /**
     * Execute the next step in the process.
     */
    public function executeNextStep(ProcessExecution $execution): ?ProcessExecutionStep
    {
        $currentState = $execution->execution_state ?? [];
        $currentStepOrder = $currentState['current_step'] ?? 0;

        $nextStep = $execution->steps()
            ->where('step_order', $currentStepOrder + 1)
            ->where('status', ProcessStepStatus::PENDING)
            ->first();

        if (! $nextStep) {
            $this->completeExecution($execution);

            return null;
        }

        return $this->executeStep($execution, $nextStep);
    }

    /**
     * Execute a specific step.
     */
    public function executeStep(ProcessExecution $execution, ProcessExecutionStep $step): ProcessExecutionStep
    {
        return DB::transaction(function () use ($execution, $step) {
            $step->update([
                'status' => ProcessStepStatus::IN_PROGRESS,
                'started_at' => now(),
            ]);

            $execution->update([
                'execution_state' => array_merge(
                    $execution->execution_state ?? [],
                    ['current_step' => $step->step_order],
                ),
            ]);

            $this->logAuditEvent(
                $execution,
                $step,
                null,
                ProcessEventType::STEP_STARTED,
                "Step '{$step->step_name}' started",
            );

            // Check if step requires approval
            if ($this->stepRequiresApproval($step)) {
                $execution->update(['status' => ProcessExecutionStatus::AWAITING_APPROVAL]);
                $this->createApprovalRequest($execution, $step);
            }

            return $step->fresh();
        });
    }

    /**
     * Complete a step.
     *
     * @param array<string, mixed> $outputData
     */
    public function completeStep(
        ProcessExecution $execution,
        ProcessExecutionStep $step,
        array $outputData = [],
    ): ProcessExecutionStep {
        return DB::transaction(function () use ($execution, $step, $outputData) {
            $step->update([
                'status' => ProcessStepStatus::COMPLETED,
                'completed_at' => now(),
                'output_data' => $outputData,
            ]);

            $this->logAuditEvent(
                $execution,
                $step,
                null,
                ProcessEventType::STEP_COMPLETED,
                "Step '{$step->step_name}' completed",
                ['output_data' => $outputData],
            );

            // Check if all steps are completed
            $pendingSteps = $execution->steps()
                ->whereIn('status', [ProcessStepStatus::PENDING, ProcessStepStatus::IN_PROGRESS])
                ->count();

            if ($pendingSteps === 0) {
                $this->completeExecution($execution);
            } else {
                $execution->update(['status' => ProcessExecutionStatus::IN_PROGRESS]);
            }

            return $step->fresh();
        });
    }

    /**
     * Fail a step.
     */
    public function failStep(
        ProcessExecution $execution,
        ProcessExecutionStep $step,
        string $errorMessage,
    ): ProcessExecutionStep {
        return DB::transaction(function () use ($execution, $step, $errorMessage) {
            $step->update([
                'status' => ProcessStepStatus::FAILED,
                'error_message' => $errorMessage,
            ]);

            $execution->update([
                'status' => ProcessExecutionStatus::FAILED,
                'error_message' => "Step '{$step->step_name}' failed: {$errorMessage}",
            ]);

            $this->logAuditEvent(
                $execution,
                $step,
                null,
                ProcessEventType::STEP_FAILED,
                "Step '{$step->step_name}' failed",
                ['error' => $errorMessage],
            );

            return $step->fresh();
        });
    }

    /**
     * Complete the entire execution.
     */
    public function completeExecution(ProcessExecution $execution): ProcessExecution
    {
        return DB::transaction(function () use ($execution) {
            $execution->update([
                'status' => ProcessExecutionStatus::COMPLETED,
                'completed_at' => now(),
            ]);

            $this->logAuditEvent(
                $execution,
                null,
                null,
                ProcessEventType::EXECUTION_COMPLETED,
                'Process execution completed',
            );

            return $execution->fresh();
        });
    }

    /**
     * Approve a pending approval.
     */
    public function approveStep(
        ProcessApproval $approval,
        int $approverId,
        ?string $notes = null,
    ): ProcessApproval {
        return DB::transaction(function () use ($approval, $approverId, $notes) {
            $approval->update([
                'status' => ProcessApprovalStatus::APPROVED,
                'approver_id' => $approverId,
                'decision_notes' => $notes,
                'decided_at' => now(),
            ]);

            $this->logAuditEvent(
                $approval->execution,
                $approval->executionStep,
                $approverId,
                ProcessEventType::APPROVAL_GRANTED,
                'Approval granted',
                ['notes' => $notes],
            );

            // Continue execution if step was waiting
            if ($approval->executionStep) {
                $this->completeStep($approval->execution, $approval->executionStep);
            }

            return $approval->fresh();
        });
    }

    /**
     * Reject a pending approval.
     */
    public function rejectStep(
        ProcessApproval $approval,
        int $approverId,
        ?string $notes = null,
    ): ProcessApproval {
        return DB::transaction(function () use ($approval, $approverId, $notes) {
            $approval->update([
                'status' => ProcessApprovalStatus::REJECTED,
                'approver_id' => $approverId,
                'decision_notes' => $notes,
                'decided_at' => now(),
            ]);

            $this->logAuditEvent(
                $approval->execution,
                $approval->executionStep,
                $approverId,
                ProcessEventType::APPROVAL_REJECTED,
                'Approval rejected',
                ['notes' => $notes],
            );

            // Fail the execution
            $approval->execution->update([
                'status' => ProcessExecutionStatus::FAILED,
                'error_message' => 'Approval rejected',
            ]);

            return $approval->fresh();
        });
    }

    /**
     * Escalate an execution or step.
     */
    public function escalate(
        ProcessExecution $execution,
        int $escalatedToId,
        int $escalatedById,
        string $reason,
        ?ProcessExecutionStep $step = null,
        ?string $notes = null,
    ): ProcessEscalation {
        return DB::transaction(function () use ($execution, $escalatedToId, $escalatedById, $reason, $step, $notes) {
            $escalation = ProcessEscalation::create([
                'team_id' => $execution->team_id,
                'execution_id' => $execution->id,
                'execution_step_id' => $step?->id,
                'escalated_to_id' => $escalatedToId,
                'escalated_by_id' => $escalatedById,
                'escalation_reason' => $reason,
                'escalation_notes' => $notes,
                'is_resolved' => false,
            ]);

            $execution->update(['status' => ProcessExecutionStatus::ESCALATED]);

            $this->logAuditEvent(
                $execution,
                $step,
                $escalatedById,
                ProcessEventType::ESCALATION_TRIGGERED,
                'Process escalated',
                ['reason' => $reason, 'notes' => $notes],
            );

            return $escalation;
        });
    }

    /**
     * Rollback an execution.
     *
     * @param array<string, mixed> $rollbackData
     */
    public function rollback(
        ProcessExecution $execution,
        int $userId,
        array $rollbackData = [],
    ): ProcessExecution {
        return DB::transaction(function () use ($execution, $userId, $rollbackData) {
            $execution->update([
                'status' => ProcessExecutionStatus::ROLLED_BACK,
                'rollback_data' => $rollbackData,
            ]);

            $this->logAuditEvent(
                $execution,
                null,
                $userId,
                ProcessEventType::ROLLBACK_COMPLETED,
                'Process rolled back',
                ['rollback_data' => $rollbackData],
            );

            return $execution->fresh();
        });
    }

    /**
     * Create execution steps from process definition.
     */
    private function createExecutionSteps(ProcessExecution $execution, ProcessDefinition $definition): void
    {
        $steps = $definition->steps ?? [];

        foreach ($steps as $index => $stepConfig) {
            ProcessExecutionStep::create([
                'execution_id' => $execution->id,
                'team_id' => $execution->team_id,
                'step_key' => $stepConfig['key'] ?? "step_{$index}",
                'step_name' => $stepConfig['name'] ?? "Step {$index}",
                'step_order' => $index + 1,
                'status' => ProcessStepStatus::PENDING,
                'step_config' => $stepConfig,
                'due_at' => $this->calculateStepDueDate($stepConfig),
            ]);
        }
    }

    /**
     * Check if a step requires approval.
     */
    private function stepRequiresApproval(ProcessExecutionStep $step): bool
    {
        $config = $step->step_config ?? [];

        return ($config['requires_approval'] ?? false) === true;
    }

    /**
     * Create an approval request for a step.
     */
    private function createApprovalRequest(ProcessExecution $execution, ProcessExecutionStep $step): ProcessApproval
    {
        $config = $step->step_config ?? [];
        $approverId = $config['approver_id'] ?? null;

        $approval = ProcessApproval::create([
            'team_id' => $execution->team_id,
            'execution_id' => $execution->id,
            'execution_step_id' => $step->id,
            'requested_by_id' => $execution->initiated_by_id,
            'approver_id' => $approverId,
            'status' => ProcessApprovalStatus::PENDING,
            'approval_notes' => $config['approval_notes'] ?? null,
            'due_at' => $this->calculateApprovalDueDate($config),
        ]);

        $this->logAuditEvent(
            $execution,
            $step,
            null,
            ProcessEventType::APPROVAL_REQUESTED,
            'Approval requested',
        );

        return $approval;
    }

    /**
     * Log an audit event.
     *
     * @param array<string, mixed>|null $eventData
     */
    private function logAuditEvent(
        ProcessExecution $execution,
        ?ProcessExecutionStep $step,
        ?int $userId,
        ProcessEventType $eventType,
        string $description,
        ?array $eventData = null,
    ): ProcessAuditLog {
        return ProcessAuditLog::create([
            'team_id' => $execution->team_id,
            'execution_id' => $execution->id,
            'execution_step_id' => $step?->id,
            'user_id' => $userId,
            'event_type' => $eventType,
            'event_description' => $description,
            'event_data' => $eventData,
            'state_before' => $step instanceof ProcessExecutionStep ? ['status' => $step->status->value] : ['status' => $execution->status->value],
            'state_after' => $step instanceof ProcessExecutionStep ? ['status' => $step->status->value] : ['status' => $execution->status->value],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Calculate SLA due date from process definition.
     */
    private function calculateSlaDueDate(ProcessDefinition $definition): ?Carbon
    {
        $slaConfig = $definition->sla_config ?? [];
        $hours = $slaConfig['hours'] ?? null;

        return $hours ? now()->addHours($hours) : null;
    }

    /**
     * Calculate step due date from step configuration.
     *
     * @param array<string, mixed> $stepConfig
     */
    private function calculateStepDueDate(array $stepConfig): ?Carbon
    {
        $hours = $stepConfig['sla_hours'] ?? null;

        return $hours ? now()->addHours($hours) : null;
    }

    /**
     * Calculate approval due date from configuration.
     *
     * @param array<string, mixed> $config
     */
    private function calculateApprovalDueDate(array $config): ?Carbon
    {
        $hours = $config['approval_sla_hours'] ?? 24;

        return now()->addHours($hours);
    }
}
