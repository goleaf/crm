<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extensions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type'); // ExtensionType enum
            $table->string('status')->default('inactive'); // ExtensionStatus enum
            $table->string('version')->default('1.0.0');
            $table->integer('priority')->default(100);
            $table->string('target_model')->nullable();
            $table->string('target_event')->nullable(); // HookEvent enum for logic hooks
            $table->string('handler_class');
            $table->string('handler_method')->default('handle');
            $table->json('configuration')->nullable();
            $table->json('permissions')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('execution_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->timestamp('last_executed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'type', 'status']);
            $table->index(['target_model', 'target_event']);
            $table->index('priority');
        });

        Schema::create('extension_executions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('extension_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status'); // success, failed, timeout
            $table->json('input_data')->nullable();
            $table->json('output_data')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('execution_time_ms')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['extension_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extension_executions');
        Schema::dropIfExists('extensions');
    }
};
