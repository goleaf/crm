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
        // Create merge_jobs table
        Schema::create('merge_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // company, contact, lead, opportunity, account
            $table->string('primary_model_type');
            $table->unsignedBigInteger('primary_model_id');
            $table->string('duplicate_model_type');
            $table->unsignedBigInteger('duplicate_model_id');
            $table->string('status')->default('pending'); // pending, processing, completed, failed, cancelled
            $table->json('merge_rules')->nullable();
            $table->json('field_selections')->nullable();
            $table->json('transferred_relationships')->nullable();
            $table->json('merge_preview')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status']);
            $table->index(['primary_model_type', 'primary_model_id']);
            $table->index(['duplicate_model_type', 'duplicate_model_id']);
        });

        // Create data_integrity_checks table
        Schema::create('data_integrity_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // orphaned_records, missing_relationships, duplicate_detection, etc.
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->string('target_model')->nullable();
            $table->json('check_parameters')->nullable();
            $table->json('results')->nullable();
            $table->integer('issues_found')->default(0);
            $table->integer('issues_fixed')->default(0);
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status']);
            $table->index(['type', 'status']);
        });

        // Create backup_jobs table
        Schema::create('backup_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // full, incremental, differential, database_only, files_only
            $table->string('status')->default('pending'); // pending, running, completed, failed, expired
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('backup_config')->nullable();
            $table->string('backup_path')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('checksum')->nullable();
            $table->json('verification_results')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status']);
            $table->index(['type', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_jobs');
        Schema::dropIfExists('data_integrity_checks');
        Schema::dropIfExists('merge_jobs');
    }
};
