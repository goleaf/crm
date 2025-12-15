<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Export configuration
            $table->string('name');
            $table->string('model_type'); // Company, People, Opportunity, etc.
            $table->string('format')->default('csv'); // csv, xlsx
            $table->json('template_config')->nullable(); // Template configuration
            $table->json('selected_fields')->nullable(); // Selected fields for export
            $table->json('filters')->nullable(); // Applied filters
            $table->json('options')->nullable(); // Additional options

            // Export scope
            $table->string('scope')->default('all'); // all, filtered, selected
            $table->json('record_ids')->nullable(); // Specific record IDs for 'selected' scope

            // Status and progress
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->integer('total_records')->default(0);
            $table->integer('processed_records')->default(0);
            $table->integer('successful_records')->default(0);
            $table->integer('failed_records')->default(0);

            // File information
            $table->string('file_path')->nullable();
            $table->string('file_disk')->default('local');
            $table->integer('file_size')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Error handling
            $table->json('errors')->nullable();
            $table->text('error_message')->nullable();

            // Timestamps
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['team_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index(['model_type', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_jobs');
    }
};
