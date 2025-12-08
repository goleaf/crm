<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('notable.table_name', 'notables'), function (Blueprint $table): void {
            $table->id();

            $table->foreignId(config('notable.team_column', 'team_id'))
                ->constrained('teams')
                ->cascadeOnDelete();

            $table->text('note');
            $table->morphs('notable');
            $table->nullableMorphs('creator');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('notable.table_name', 'notables'));
    }
};
