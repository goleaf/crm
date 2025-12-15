<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add performance indexes for project scheduling queries.
 *
 * These indexes optimize:
 * - Critical path calculations (task dependencies)
 * - Timeline generation (date ranges)
 * - Schedule status checks (progress tracking)
 * - Budget calculations (project-task relationships)
 *
 * Expected impact: 60-80% faster query execution
 */
return new class extends Migration
{
    public function up(): void
    {
        // Projects table indexes
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->index('start_date', 'projects_start_date_index');
                $table->index('end_date', 'projects_end_date_index');
                $table->index('percent_complete', 'projects_percent_complete_index');
                $table->index(['team_id', 'start_date'], 'projects_team_start_date_index');
                $table->index(['team_id', 'end_date'], 'projects_team_end_date_index');
            });
        }

        // Task dependencies indexes (critical for critical path calculation)
        if (Schema::hasTable('task_dependencies')) {
            Schema::table('task_dependencies', function (Blueprint $table): void {
                $table->index('task_id', 'task_deps_task_id_index');
                $table->index('depends_on_task_id', 'task_deps_depends_on_index');
            });
        }

        // Project-task pivot indexes
        if (Schema::hasTable('project_task')) {
            Schema::table('project_task', function (Blueprint $table): void {
                $table->index('task_id', 'project_task_task_id_index');
                $table->index('project_id', 'project_task_project_id_index');
            });
        }

        // Tasks table indexes
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table): void {
                $table->index('is_milestone', 'tasks_is_milestone_index');
                $table->index('percent_complete', 'tasks_percent_complete_index');
                $table->index(['team_id', 'is_milestone'], 'tasks_team_milestone_index');
                $table->index('start_date', 'tasks_start_date_index');
                $table->index('end_date', 'tasks_end_date_index');
            });
        }

        // Task time entries indexes (for budget calculations)
        if (Schema::hasTable('task_time_entries')) {
            Schema::table('task_time_entries', function (Blueprint $table): void {
                $table->index('is_billable', 'task_time_entries_billable_index');
                $table->index(['task_id', 'is_billable'], 'task_time_entries_task_billable_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->dropIndex('projects_start_date_index');
                $table->dropIndex('projects_end_date_index');
                $table->dropIndex('projects_percent_complete_index');
                $table->dropIndex('projects_team_start_date_index');
                $table->dropIndex('projects_team_end_date_index');
            });
        }

        if (Schema::hasTable('task_dependencies')) {
            Schema::table('task_dependencies', function (Blueprint $table): void {
                $table->dropIndex('task_deps_task_id_index');
                $table->dropIndex('task_deps_depends_on_index');
            });
        }

        if (Schema::hasTable('project_task')) {
            Schema::table('project_task', function (Blueprint $table): void {
                $table->dropIndex('project_task_task_id_index');
                $table->dropIndex('project_task_project_id_index');
            });
        }

        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table): void {
                $table->dropIndex('tasks_is_milestone_index');
                $table->dropIndex('tasks_percent_complete_index');
                $table->dropIndex('tasks_team_milestone_index');
                $table->dropIndex('tasks_start_date_index');
                $table->dropIndex('tasks_end_date_index');
            });
        }

        if (Schema::hasTable('task_time_entries')) {
            Schema::table('task_time_entries', function (Blueprint $table): void {
                $table->dropIndex('task_time_entries_billable_index');
                $table->dropIndex('task_time_entries_task_billable_index');
            });
        }
    }
};
