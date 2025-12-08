<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocr_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('ocr_templates')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size'); // bytes
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->json('extracted_data')->nullable();
            $table->text('raw_text')->nullable();
            $table->decimal('confidence_score', 5, 4)->nullable(); // 0.0000 to 1.0000
            $table->decimal('processing_time', 8, 3)->nullable(); // seconds
            $table->json('validation_errors')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status']);
            $table->index('template_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('processed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocr_documents');
    }
};
