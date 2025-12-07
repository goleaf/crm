<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('creator_id')
                ->constrained('tasks')
                ->nullOnDelete();
        });

        Schema::create('task_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->nullable();
            $table->timestamps();
        });

        Schema::create('task_task_category', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('task_category_id')->constrained('task_categories')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['task_id', 'task_category_id']);
        });

        Schema::create('task_dependencies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('depends_on_task_id')->constrained('tasks')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['task_id', 'depends_on_task_id']);
        });

        Schema::create('task_checklist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('title');
            $table->boolean('is_completed')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('task_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('task_comments')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('task_time_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('task_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('remind_at')->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->string('channel')->default('database');
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('task_recurrences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('frequency');
            $table->unsignedInteger('interval')->default(1);
            $table->json('days_of_week')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->unsignedInteger('max_occurrences')->nullable();
            $table->string('timezone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('task_delegations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('delegated_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_delegations');
        Schema::dropIfExists('task_recurrences');
        Schema::dropIfExists('task_reminders');
        Schema::dropIfExists('task_time_entries');
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('task_checklist_items');
        Schema::dropIfExists('task_dependencies');
        Schema::dropIfExists('task_task_category');
        Schema::dropIfExists('task_categories');

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
