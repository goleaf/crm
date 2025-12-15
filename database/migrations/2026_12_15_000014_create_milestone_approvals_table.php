<?php

declare(strict_types=1);

use App\Enums\MilestoneApprovalStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestone_approvals', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('milestone_id')->constrained('milestones')->cascadeOnDelete();
            $table->unsignedInteger('step_order')->default(1);
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval_criteria')->nullable();
            $table->string('status', 20)->default(MilestoneApprovalStatus::PENDING->value);
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_comment')->nullable();

            $table->timestamps();

            $table->index('milestone_id', 'milestone_approvals_milestone_index');
            $table->index('approver_id', 'milestone_approvals_approver_index');
            $table->index('status', 'milestone_approvals_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestone_approvals');
    }
};

