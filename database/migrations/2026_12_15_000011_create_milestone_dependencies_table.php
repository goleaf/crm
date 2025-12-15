<?php

declare(strict_types=1);

use App\Enums\DependencyType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestone_dependencies', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('predecessor_id')->constrained('milestones')->cascadeOnDelete();
            $table->foreignId('successor_id')->constrained('milestones')->cascadeOnDelete();

            $table->string('dependency_type', 30)->default(DependencyType::FINISH_TO_START->value);
            $table->integer('lag_days')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['predecessor_id', 'successor_id'], 'milestone_dependencies_unique');
            $table->index('predecessor_id', 'milestone_dependencies_predecessor_index');
            $table->index('successor_id', 'milestone_dependencies_successor_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestone_dependencies');
    }
};

