<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add performance indexes for calendar queries.
     */
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            // Index for type filtering (used in calendar filters)
            $table->index('type', 'calendar_events_type_index');

            // Index for creator filtering (team member filter)
            $table->index('creator_id', 'calendar_events_creator_id_index');

            // Composite index for complex filtered queries
            // Covers: team_id + type + status + start_at (most common query pattern)
            $table->index(
                ['team_id', 'type', 'status', 'start_at'],
                'calendar_events_team_type_status_start_index'
            );

            // Index for recurrence queries
            $table->index('recurrence_parent_id', 'calendar_events_recurrence_parent_index');

            // Index for sync operations
            $table->index(['sync_provider', 'sync_external_id'], 'calendar_events_sync_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->dropIndex('calendar_events_type_index');
            $table->dropIndex('calendar_events_creator_id_index');
            $table->dropIndex('calendar_events_team_type_status_start_index');
            $table->dropIndex('calendar_events_recurrence_parent_index');
            $table->dropIndex('calendar_events_sync_index');
        });
    }
};
