<?php

declare(strict_types=1);

use App\Enums\MilestonePriority;
use App\Enums\MilestoneStatus;
use App\Enums\MilestoneType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestones', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->date('target_date');
            $table->date('actual_completion_date')->nullable();

            $table->string('milestone_type', 50)->default(MilestoneType::PHASE_COMPLETION->value);
            $table->string('priority_level', 20)->default(MilestonePriority::MEDIUM->value);
            $table->string('status', 30)->default(MilestoneStatus::NOT_STARTED->value);

            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->integer('schedule_variance_days')->default(0);

            $table->boolean('is_critical')->default(false);
            $table->boolean('is_at_risk')->default(false);

            $table->unsignedTinyInteger('last_progress_threshold_notified')->default(0);
            $table->json('reminders_sent')->nullable();
            $table->timestamp('overdue_notified_at')->nullable();

            $table->json('stakeholder_ids')->nullable();
            $table->json('reference_links')->nullable();
            $table->text('notes')->nullable();

            $table->boolean('requires_approval')->default(false);
            $table->timestamp('submitted_for_approval_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'status'], 'milestones_project_status_index');
            $table->index('target_date', 'milestones_target_date_index');
            $table->index('owner_id', 'milestones_owner_index');
            $table->index('team_id', 'milestones_team_index');
            $table->index('is_critical', 'milestones_critical_index');
            $table->index('is_at_risk', 'milestones_at_risk_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestones');
    }
};

