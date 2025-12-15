<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_indices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->nullable()->constrained('teams')->cascadeOnDelete();
            $table->string('term');
            $table->string('module');
            $table->string('searchable_type');
            $table->unsignedBigInteger('searchable_id');
            $table->json('metadata')->nullable();
            $table->decimal('ranking_score', 8, 2)->default(1.0);
            $table->unsignedInteger('search_count')->default(0);
            $table->timestamp('last_searched_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'term']);
            $table->index(['team_id', 'module']);
            $table->index(['searchable_type', 'searchable_id']);
            $table->index(['ranking_score', 'search_count']);
            $table->unique(['team_id', 'term', 'module', 'searchable_type', 'searchable_id'], 'search_indices_unique');
        });

        Schema::create('search_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->nullable()->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('query');
            $table->string('module')->nullable();
            $table->json('filters')->nullable();
            $table->unsignedInteger('results_count')->default(0);
            $table->decimal('execution_time', 8, 4)->nullable();
            $table->timestamp('searched_at');
            $table->timestamps();

            $table->index(['team_id', 'user_id']);
            $table->index(['team_id', 'module']);
            $table->index(['query', 'searched_at']);
        });

        Schema::create('search_suggestions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->nullable()->constrained('teams')->cascadeOnDelete();
            $table->string('term');
            $table->string('module')->nullable();
            $table->unsignedInteger('frequency')->default(1);
            $table->decimal('relevance_score', 8, 2)->default(1.0);
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['team_id', 'term']);
            $table->index(['team_id', 'module']);
            $table->index(['relevance_score', 'frequency']);
            $table->unique(['team_id', 'term', 'module'], 'search_suggestions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_suggestions');
        Schema::dropIfExists('search_histories');
        Schema::dropIfExists('search_indices');
    }
};
