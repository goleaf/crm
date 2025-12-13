<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add missing fields to opportunities table
        if (Schema::hasTable('opportunities')) {
            Schema::table('opportunities', function (Blueprint $table): void {
                // Stage, probability, amount, weighted amount, expected close, competitors, next steps
                if (!Schema::hasColumn('opportunities', 'stage')) {
                    $table->string('stage')->nullable()->after('name');
                }
                if (!Schema::hasColumn('opportunities', 'probability')) {
                    $table->decimal('probability', 5, 2)->nullable()->after('stage')->comment('Probability as percentage (0-100)');
                }
                if (!Schema::hasColumn('opportunities', 'amount')) {
                    $table->decimal('amount', 15, 2)->nullable()->after('probability');
                }
                if (!Schema::hasColumn('opportunities', 'weighted_amount')) {
                    $table->decimal('weighted_amount', 15, 2)->nullable()->after('amount')->comment('Amount * Probability / 100');
                }
                if (!Schema::hasColumn('opportunities', 'expected_close_date')) {
                    $table->date('expected_close_date')->nullable()->after('weighted_amount');
                }
                if (!Schema::hasColumn('opportunities', 'competitors')) {
                    $table->json('competitors')->nullable()->after('expected_close_date');
                }
                if (!Schema::hasColumn('opportunities', 'next_steps')) {
                    $table->text('next_steps')->nullable()->after('competitors');
                }
                if (!Schema::hasColumn('opportunities', 'win_loss_reason')) {
                    $table->string('win_loss_reason')->nullable()->after('next_steps');
                }
                if (!Schema::hasColumn('opportunities', 'forecast_category')) {
                    $table->string('forecast_category')->nullable()->after('win_loss_reason');
                }
                
                // Add indexes for performance
                $table->index(['stage']);
                $table->index(['expected_close_date']);
                $table->index(['forecast_category']);
            });
        }

        // Add missing fields to cases table for SLA timers, escalation metadata, queue assignment, threading ids
        if (Schema::hasTable('cases')) {
            Schema::table('cases', function (Blueprint $table): void {
                // Add account_id if not exists (for bidirectional relationship)
                if (!Schema::hasColumn('cases', 'account_id')) {
                    $table->foreignId('account_id')->nullable()->after('contact_id')->constrained('accounts')->nullOnDelete();
                }
                
                // Threading and portal visibility (some may already exist from previous migrations)
                if (!Schema::hasColumn('cases', 'thread_id')) {
                    $table->string('thread_id')->nullable()->after('thread_reference')->comment('Unique thread identifier for email chains');
                }
                if (!Schema::hasColumn('cases', 'parent_case_id')) {
                    $table->foreignId('parent_case_id')->nullable()->after('thread_id')->constrained('cases')->nullOnDelete();
                }
                
                // Add indexes for performance
                $table->index(['thread_id']);
                $table->index(['parent_case_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('opportunities')) {
            Schema::table('opportunities', function (Blueprint $table): void {
                $columns = [
                    'stage', 'probability', 'amount', 'weighted_amount', 
                    'expected_close_date', 'competitors', 'next_steps', 
                    'win_loss_reason', 'forecast_category'
                ];
                
                foreach ($columns as $column) {
                    if (Schema::hasColumn('opportunities', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('cases')) {
            Schema::table('cases', function (Blueprint $table): void {
                $columns = ['thread_id', 'parent_case_id'];
                
                foreach ($columns as $column) {
                    if (Schema::hasColumn('cases', $column)) {
                        if ($column === 'parent_case_id') {
                            $table->dropForeign(['parent_case_id']);
                        }
                        $table->dropColumn($column);
                    }
                }
                
                if (Schema::hasColumn('cases', 'account_id')) {
                    $table->dropForeign(['account_id']);
                    $table->dropColumn('account_id');
                }
            });
        }
    }
};