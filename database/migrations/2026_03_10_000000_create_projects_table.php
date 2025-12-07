<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('template_id')->nullable()->constrained('projects')->onDelete('set null');

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('planning');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();

            $table->decimal('budget', 15, 2)->nullable();
            $table->decimal('actual_cost', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');

            $table->decimal('percent_complete', 5, 2)->default(0);

            $table->json('phases')->nullable();
            $table->json('milestones')->nullable();
            $table->json('deliverables')->nullable();
            $table->json('risks')->nullable();
            $table->json('issues')->nullable();
            $table->json('documentation')->nullable();
            $table->json('dashboard_config')->nullable();

            $table->boolean('is_template')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'is_template']);
        });

        Schema::create('project_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->decimal('allocation_percentage', 5, 2)->default(100);
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });

        Schema::create('project_task', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_task');
        Schema::dropIfExists('project_user');
        Schema::dropIfExists('projects');
    }
};
