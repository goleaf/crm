<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('territories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('type', ['geographic', 'product', 'hybrid'])->default('geographic');
            $table->text('description')->nullable();

            // Hierarchy
            $table->foreignId('parent_id')->nullable()->constrained('territories')->nullOnDelete();
            $table->integer('level')->default(0);
            $table->string('path')->nullable(); // Materialized path for efficient queries

            // Assignment rules (JSON)
            $table->json('assignment_rules')->nullable();

            // Quotas
            $table->decimal('revenue_quota', 15, 2)->nullable();
            $table->integer('unit_quota')->nullable();
            $table->string('quota_period')->nullable(); // monthly, quarterly, yearly

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'is_active']);
            $table->index('parent_id');
            $table->index('type');
        });

        Schema::create('territory_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('territory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['owner', 'member', 'viewer'])->default('member');
            $table->boolean('is_primary')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->unique(['territory_id', 'user_id']);
            $table->index(['user_id', 'is_primary']);
        });

        Schema::create('territory_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('territory_id')->constrained()->cascadeOnDelete();
            $table->morphs('record'); // Polymorphic relation to any model
            $table->boolean('is_primary')->default(true);
            $table->timestamp('assigned_at')->useCurrent();
            $table->text('assignment_reason')->nullable();
            $table->timestamps();

            $table->index(['territory_id', 'is_primary']);
        });

        Schema::create('territory_transfers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('from_territory_id')->constrained('territories')->cascadeOnDelete();
            $table->foreignId('to_territory_id')->constrained('territories')->cascadeOnDelete();
            $table->morphs('record');
            $table->foreignId('initiated_by')->constrained('users');
            $table->text('reason')->nullable();
            $table->timestamp('transferred_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('territory_quotas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('territory_id')->constrained()->cascadeOnDelete();
            $table->string('period'); // 2024-Q1, 2024-01, etc.
            $table->decimal('revenue_target', 15, 2)->nullable();
            $table->integer('unit_target')->nullable();
            $table->decimal('revenue_actual', 15, 2)->default(0);
            $table->integer('unit_actual')->default(0);
            $table->timestamps();

            $table->unique(['territory_id', 'period']);
        });

        Schema::create('territory_overlaps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('territory_a_id')->constrained('territories')->cascadeOnDelete();
            $table->foreignId('territory_b_id')->constrained('territories')->cascadeOnDelete();
            $table->enum('resolution_strategy', ['split', 'priority', 'manual'])->default('manual');
            $table->integer('priority_territory_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['territory_a_id', 'territory_b_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('territory_overlaps');
        Schema::dropIfExists('territory_quotas');
        Schema::dropIfExists('territory_transfers');
        Schema::dropIfExists('territory_records');
        Schema::dropIfExists('territory_assignments');
        Schema::dropIfExists('territories');
    }
};
