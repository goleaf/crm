<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestone_task', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('milestone_id')->constrained('milestones')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();

            $table->decimal('weight', 8, 4)->default(1);

            $table->timestamps();

            $table->unique(['milestone_id', 'task_id'], 'milestone_task_unique');
            $table->index('milestone_id', 'milestone_task_milestone_index');
            $table->index('task_id', 'milestone_task_task_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestone_task');
    }
};

