<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocr_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('document_type'); // invoice, receipt, business_card, contract, etc.
            $table->json('field_definitions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'document_type']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocr_templates');
    }
};
