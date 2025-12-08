<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            if (! Schema::hasColumn('calendar_events', 'zap_schedule_id')) {
                $table->foreignId('zap_schedule_id')
                    ->nullable()
                    ->after('creation_source')
                    ->constrained('schedules')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('calendar_events', 'zap_metadata')) {
                $table->json('zap_metadata')
                    ->nullable()
                    ->after('zap_schedule_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            if (Schema::hasColumn('calendar_events', 'zap_schedule_id')) {
                $table->dropConstrainedForeignId('zap_schedule_id');
            }

            if (Schema::hasColumn('calendar_events', 'zap_metadata')) {
                $table->dropColumn('zap_metadata');
            }
        });
    }
};
