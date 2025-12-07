<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_definitions', function (Blueprint $table): void {
            // Workflow trigger configuration
            if (! Schema::hasColumn('process_definitions', 'trigger_type')) {
                $table->string('trigger_type', 32)->nullable()->after('status');
            }

            if (! Schema::hasColumn('process_definitions', 'target_model')) {
                $table->string('target_model', 255)->nullable()->after('trigger_type');
            }

            // Workflow conditions
            if (! Schema::hasColumn('process_definitions', 'conditions')) {
                $table->json('conditions')->nullable()->after('event_triggers');
            }

            if (! Schema::hasColumn('process_definitions', 'condition_logic')) {
                $table->string('condition_logic', 16)->default('and')->after('conditions');
            }

            // Workflow execution settings
            if (! Schema::hasColumn('process_definitions', 'allow_repeated_runs')) {
                $table->boolean('allow_repeated_runs')->default(false)->after('condition_logic');
            }

            if (! Schema::hasColumn('process_definitions', 'max_runs_per_record')) {
                $table->unsignedInteger('max_runs_per_record')->nullable()->after('allow_repeated_runs');
            }

            if (! Schema::hasColumn('process_definitions', 'schedule_config')) {
                $table->json('schedule_config')->nullable()->after('max_runs_per_record');
            }

            // Testing and logging
            if (! Schema::hasColumn('process_definitions', 'test_mode')) {
                $table->boolean('test_mode')->default(false)->after('schedule_config');
            }

            if (! Schema::hasColumn('process_definitions', 'enable_logging')) {
                $table->boolean('enable_logging')->default(true)->after('test_mode');
            }

            if (! Schema::hasColumn('process_definitions', 'log_level')) {
                $table->string('log_level', 32)->default('info')->after('enable_logging');
            }
        });
    }

    public function down(): void
    {
        Schema::table('process_definitions', function (Blueprint $table): void {
            $table->dropColumn([
                'trigger_type',
                'target_model',
                'conditions',
                'condition_logic',
                'allow_repeated_runs',
                'max_runs_per_record',
                'schedule_config',
                'test_mode',
                'enable_logging',
                'log_level',
            ]);
        });
    }
};
