<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestone_progress_snapshots', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('milestone_id')->constrained('milestones')->cascadeOnDelete();
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->integer('schedule_variance_days')->default(0);
            $table->unsignedInteger('remaining_tasks_count')->default(0);
            $table->unsignedInteger('blocked_tasks_count')->default(0);

            $table->timestamps();

            $table->index(['milestone_id', 'created_at'], 'milestone_snapshots_milestone_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestone_progress_snapshots');
    }
};

