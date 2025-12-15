<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_searches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('resource')->default('global');
            $table->string('query')->nullable();
            $table->json('filters')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'resource']);
            $table->unique(['team_id', 'user_id', 'name', 'resource']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_searches');
    }
};
