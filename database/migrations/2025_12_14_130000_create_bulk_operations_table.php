<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_operations', function (Blueprint $table): void {
            $table->id();
            $table->string('type'); // update, delete, assign
            $table->string('status'); // pending, processing, completed, failed, cancelled
            $table->string('model_type'); // The model class being operated on
            $table->integer('total_records')->default(0);
            $table->integer('processed_records')->default(0);
            $table->integer('failed_records')->default(0);
            $table->integer('batch_size')->default(100);
            $table->json('operation_data')->nullable(); // Data for the operation (e.g., field updates)
            $table->json('errors')->nullable(); // Array of error messages
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['team_id', 'status']);
            $table->index(['model_type', 'status']);
            $table->index(['creator_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_operations');
    }
};
