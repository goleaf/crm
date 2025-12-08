<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            // Foreign key index (critical for recurrence relationships)
            $table->index('recurrence_parent_id');

            // Filtering indexes for recurring events
            $table->index('recurrence_rule');
            $table->index('recurrence_end_date');

            // Composite indexes for common query patterns
            $table->index(['team_id', 'recurrence_parent_id'], 'idx_team_recurrence_parent');
            $table->index(['recurrence_parent_id', 'start_at'], 'idx_parent_start');
            $table->index(['team_id', 'recurrence_rule'], 'idx_team_recurrence_rule');

            // Calendar view optimization (date range queries)
            $table->index(['team_id', 'start_at', 'end_at'], 'idx_team_date_range');

            // Sync status queries for external calendar integration
            $table->index(['sync_status', 'sync_provider'], 'idx_sync_status_provider');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->dropIndex('recurrence_parent_id');
            $table->dropIndex('recurrence_rule');
            $table->dropIndex('recurrence_end_date');
            $table->dropIndex('idx_team_recurrence_parent');
            $table->dropIndex('idx_parent_start');
            $table->dropIndex('idx_team_recurrence_rule');
            $table->dropIndex('idx_team_date_range');
            $table->dropIndex('idx_sync_status_provider');
        });
    }
};
