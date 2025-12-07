<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('key')->unique();
            $table->string('entity_type')->nullable();
            $table->text('description')->nullable();
            $table->longText('layout');
            $table->json('merge_fields')->nullable();
            $table->json('styling')->nullable();
            $table->json('watermark')->nullable();
            $table->json('permissions')->nullable();
            $table->boolean('encryption_enabled')->default(false);
            $table->string('encryption_password')->nullable();
            $table->integer('version')->default(1);
            $table->foreignId('parent_template_id')->nullable()->constrained('pdf_templates')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'entity_type']);
            $table->index(['key', 'is_active']);
        });

        Schema::create('pdf_generations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pdf_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('entity');
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->integer('page_count')->default(1);
            $table->json('merge_data')->nullable();
            $table->json('generation_options')->nullable();
            $table->boolean('has_watermark')->default(false);
            $table->boolean('is_encrypted')->default(false);
            $table->string('status')->default('completed');
            $table->text('error_message')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index(['team_id', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_generations');
        Schema::dropIfExists('pdf_templates');
    }
};
