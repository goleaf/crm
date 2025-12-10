<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add performance indexes for SupportCase table.
 *
 * These indexes optimize:
 * - Table sorting by created_at (default sort)
 * - Filtering by status, priority, type, channel
 * - SLA breach queries (sla_due_at + resolved_at)
 * - Assignee and company lookups
 *
 * @see App\Filament\Resources\SupportCaseResource
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table): void {
            // Composite index for default sort with team scoping
            if (! $this->indexExists('cases', 'idx_cases_team_created')) {
                $table->index(['team_id', 'created_at'], 'idx_cases_team_created');
            }

            // Index for SLA breach filter (overdue cases)
            if (! $this->indexExists('cases', 'idx_cases_sla_overdue')) {
                $table->index(['sla_due_at', 'resolved_at'], 'idx_cases_sla_overdue');
            }

            // Index for assignee filtering
            if (! $this->indexExists('cases', 'idx_cases_assigned_to')) {
                $table->index(['assigned_to_id'], 'idx_cases_assigned_to');
            }

            // Index for company filtering
            if (! $this->indexExists('cases', 'idx_cases_company')) {
                $table->index(['company_id'], 'idx_cases_company');
            }

            // Composite index for priority + status filtering (common filter combo)
            if (! $this->indexExists('cases', 'idx_cases_priority_status')) {
                $table->index(['priority', 'status'], 'idx_cases_priority_status');
            }

            // Index for type filtering
            if (! $this->indexExists('cases', 'idx_cases_type')) {
                $table->index(['type'], 'idx_cases_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table): void {
            $table->dropIndex('idx_cases_team_created');
            $table->dropIndex('idx_cases_sla_overdue');
            $table->dropIndex('idx_cases_assigned_to');
            $table->dropIndex('idx_cases_company');
            $table->dropIndex('idx_cases_priority_status');
            $table->dropIndex('idx_cases_type');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);

        return collect($indexes)->contains(fn (array $index): bool => $index['name'] === $indexName);
    }
};
