<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestone_templates', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable();
            $table->json('template_data');
            $table->unsignedInteger('usage_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index('team_id', 'milestone_templates_team_index');
            $table->index('category', 'milestone_templates_category_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestone_templates');
    }
};

