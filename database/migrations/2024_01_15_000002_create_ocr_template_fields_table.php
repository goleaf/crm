<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocr_template_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')->constrained('ocr_templates')->cascadeOnDelete();
            $table->string('field_name');
            $table->string('field_type'); // string, number, date, email, phone, etc.
            $table->text('extraction_pattern')->nullable(); // Regex pattern
            $table->boolean('required')->default(false);
            $table->json('validation_rules')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('template_id');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocr_template_fields');
    }
};
