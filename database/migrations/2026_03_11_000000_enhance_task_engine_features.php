<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add missing fields to tasks table
        Schema::table('tasks', function (Blueprint $table): void {
            $table->timestamp('start_date')->nullable()->after('parent_id');
            $table->timestamp('end_date')->nullable()->after('start_date');
            $table->unsignedInteger('estimated_duration_minutes')->nullable()->after('end_date');
            $table->decimal('percent_complete', 5, 2)->default(0)->after('estimated_duration_minutes');
            $table->boolean('is_milestone')->default(false)->after('percent_complete');
        });

        // Add billable flag to time entries
        Schema::table('task_time_entries', function (Blueprint $table): void {
            $table->boolean('is_billable')->default(false)->after('duration_minutes');
            $table->decimal('billing_rate', 10, 2)->nullable()->after('is_billable');
        });

        // Create task templates table
        Schema::create('task_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('estimated_duration_minutes')->nullable();
            $table->boolean('is_milestone')->default(false);
            $table->json('default_assignees')->nullable();
            $table->json('checklist_items')->nullable();
            $table->timestamps();
        });

        // Create task template dependencies table
        Schema::create('task_template_dependencies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_template_id')->constrained('task_templates')->cascadeOnDelete();
            $table->foreignId('depends_on_template_id')->constrained('task_templates')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['task_template_id', 'depends_on_template_id'], 'task_template_deps_unique');
        });

        // Add template reference to tasks
        Schema::table('tasks', function (Blueprint $table): void {
            $table->foreignId('template_id')->nullable()->after('parent_id')->constrained('task_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('template_id');
        });

        Schema::dropIfExists('task_template_dependencies');
        Schema::dropIfExists('task_templates');

        Schema::table('task_time_entries', function (Blueprint $table): void {
            $table->dropColumn(['is_billable', 'billing_rate']);
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn([
                'start_date',
                'end_date',
                'estimated_duration_minutes',
                'percent_complete',
                'is_milestone',
            ]);
        });
    }
};
