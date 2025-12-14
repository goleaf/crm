<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // csv, xlsx, xls, vcard
            $table->string('model_type'); // Company, People, etc.
            $table->string('file_path');
            $table->string('original_filename');
            $table->integer('file_size');
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->json('mapping')->nullable(); // field mapping configuration
            $table->json('duplicate_rules')->nullable(); // duplicate detection rules
            $table->json('validation_rules')->nullable(); // validation configuration
            $table->integer('total_rows')->nullable();
            $table->integer('processed_rows')->default(0);
            $table->integer('successful_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->integer('duplicate_rows')->default(0);
            $table->json('errors')->nullable(); // per-row errors
            $table->json('preview_data')->nullable(); // sample data for preview
            $table->json('statistics')->nullable(); // import statistics
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index('model_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_jobs');
    }
};
