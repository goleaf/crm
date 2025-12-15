<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('taxonomy.table_names');
        $tableNames = array_merge([
            'taxonomies' => 'taxonomies',
            'taxonomables' => 'taxonomables',
        ], (array) config('taxonomy.table_names', []));

        $morphType = config('taxonomy.morph_type', 'uuid');

        Schema::create($tableNames['taxonomies'], function (Blueprint $table) use ($tableNames): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('type')->index();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained($tableNames['taxonomies']);
            $table->integer('sort_order')->default(0);
            $table->unsignedInteger('lft')->nullable()->index();
            $table->unsignedInteger('rgt')->nullable()->index();
            $table->unsignedInteger('depth')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Unique slugs per type for active records only (ignore soft-deleted)
            $table->unique(['slug', 'type', 'team_id', 'deleted_at']);
            // Composite index for type, lft, & rgt
            $table->index(['team_id', 'type', 'lft', 'rgt']);
            $table->index(['team_id', 'type', 'slug']);
        });

        Schema::create($tableNames['taxonomables'], function (Blueprint $table) use ($morphType, $tableNames): void {
            $table->id();
            $table->foreignId('taxonomy_id')->constrained($tableNames['taxonomies'])->cascadeOnDelete();

            $name = 'taxonomable';
            match ($morphType) {
                'uuid' => $table->uuidMorphs($name),
                'ulid' => $table->ulidMorphs($name),
                default => $table->morphs($name),
            };
            $table->timestamps();
            $table->unique(['taxonomy_id', 'taxonomable_type', 'taxonomable_id']);
        });
    }

    public function down(): void
    {
        $tableNames = config('taxonomy.table_names');
        $tableNames = array_merge([
            'taxonomies' => 'taxonomies',
            'taxonomables' => 'taxonomables',
        ], (array) config('taxonomy.table_names', []));

        Schema::dropIfExists($tableNames['taxonomables']);
        Schema::dropIfExists($tableNames['taxonomies']);
    }
};
