<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'name']);
        });

        Schema::create('group_people', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('people_id')->constrained('people')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['group_id', 'people_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_people');
        Schema::dropIfExists('groups');
    }
};
