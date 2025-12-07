<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_merge_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('primary_contact_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('duplicate_contact_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('merged_by')->constrained('users');
            $table->json('merge_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_merge_logs');
    }
};
