<?php

declare(strict_types=1);

use App\Enums\ProcessApprovalStatus;
use App\Enums\ProcessExecutionStatus;
use App\Enums\ProcessStatus;
use App\Enums\ProcessStepStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_definitions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status', 32)->default(ProcessStatus::DRAFT->value);

            $table->unsignedInteger('version')->default(1);
            $table->json('steps')->nullable();
            $table->json('business_rules')->nullable();
            $table->json('event_triggers')->nullable();
            $table->json('sla_config')->nullable();
            $table->json('escalation_rules')->nullable();
            $table->json('metadata')->nullable();

            $table->text('documentation')->nullable();
            $table->foreignId('template_id')->nullable()->constrained('process_definitions')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status']);
            $table->index(['slug', 'version']);
        });

        Schema::create('process_executions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('process_definition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('initiated_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status', 32)->default(ProcessExecutionStatus::PENDING->value);
            $table->unsignedInteger('process_version');

            $table->json('context_data')->nullable();
            $table->json('execution_state')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('sla_due_at')->nullable();

            $table->text('error_message')->nullable();
            $table->json('rollback_data')->nullable();

            $table->timestamps();

            $table->index(['team_id', 'status']);
            $table->index(['process_definition_id', 'status']);
            $table->index('sla_due_at');
        });

        Schema::create('process_execution_steps', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('execution_id')->constrained('process_executions')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('step_key');
            $table->string('step_name');
            $table->unsignedInteger('step_order');
            $table->string('status', 32)->default(ProcessStepStatus::PENDING->value);

            $table->json('step_config')->nullable();
            $table->json('input_data')->nullable();
            $table->json('output_data')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('due_at')->nullable();

            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['execution_id', 'step_order']);
            $table->index(['status', 'due_at']);
        });

        Schema::create('process_approvals', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('execution_id')->constrained('process_executions')->cascadeOnDelete();
            $table->foreignId('execution_step_id')->nullable()->constrained('process_execution_steps')->nullOnDelete();
            $table->foreignId('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status', 32)->default(ProcessApprovalStatus::PENDING->value);
            $table->text('approval_notes')->nullable();
            $table->text('decision_notes')->nullable();

            $table->timestamp('due_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('escalated_at')->nullable();

            $table->timestamps();

            $table->index(['execution_id', 'status']);
            $table->index(['approver_id', 'status']);
            $table->index('due_at');
        });

        Schema::create('process_escalations', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('execution_id')->constrained('process_executions')->cascadeOnDelete();
            $table->foreignId('execution_step_id')->nullable()->constrained('process_execution_steps')->nullOnDelete();
            $table->foreignId('escalated_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('escalated_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('escalation_reason');
            $table->text('escalation_notes')->nullable();
            $table->json('escalation_config')->nullable();

            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();

            $table->timestamps();

            $table->index(['execution_id', 'is_resolved']);
            $table->index('escalated_to_id');
        });

        Schema::create('process_audit_logs', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('execution_id')->constrained('process_executions')->cascadeOnDelete();
            $table->foreignId('execution_step_id')->nullable()->constrained('process_execution_steps')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('event_type', 64);
            $table->text('event_description')->nullable();
            $table->json('event_data')->nullable();
            $table->json('state_before')->nullable();
            $table->json('state_after')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index(['execution_id', 'event_type']);
            $table->index(['team_id', 'created_at']);
        });

        Schema::create('process_analytics', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('process_definition_id')->constrained()->cascadeOnDelete();

            $table->date('metric_date');
            $table->unsignedInteger('executions_started')->default(0);
            $table->unsignedInteger('executions_completed')->default(0);
            $table->unsignedInteger('executions_failed')->default(0);
            $table->unsignedInteger('sla_breaches')->default(0);
            $table->unsignedInteger('escalations')->default(0);

            $table->unsignedBigInteger('avg_completion_time_seconds')->nullable();
            $table->unsignedBigInteger('min_completion_time_seconds')->nullable();
            $table->unsignedBigInteger('max_completion_time_seconds')->nullable();

            $table->timestamps();

            $table->unique(['process_definition_id', 'metric_date']);
            $table->index(['team_id', 'metric_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_analytics');
        Schema::dropIfExists('process_audit_logs');
        Schema::dropIfExists('process_escalations');
        Schema::dropIfExists('process_approvals');
        Schema::dropIfExists('process_execution_steps');
        Schema::dropIfExists('process_executions');
        Schema::dropIfExists('process_definitions');
    }
};
