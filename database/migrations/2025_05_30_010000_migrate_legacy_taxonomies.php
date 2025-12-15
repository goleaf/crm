<?php

declare(strict_types=1);

use App\Models\KnowledgeArticle;
use App\Models\Product;
use App\Models\Task;
use App\Models\Taxonomy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('taxonomies') || ! Schema::hasTable('taxonomables')) {
            return;
        }

        $now = now();

        $taskCategoryMap = $this->importSimpleCategory(
            table: 'task_categories',
            type: 'task_category',
            slugColumn: null,
            descriptionColumn: null,
            metaColumns: ['color'],
        );
        if ($taskCategoryMap !== []) {
            Taxonomy::rebuildNestedSet('task_category');
        }

        $this->attachPivot(
            pivotTable: 'task_task_category',
            taxonomyMap: $taskCategoryMap,
            taxonomyType: 'task_category',
            modelClass: Task::class,
            modelColumn: 'task_id',
            categoryColumn: 'task_category_id',
            now: $now,
        );

        $productCategoryMap = $this->importHierarchicalCategory(
            table: 'product_categories',
            type: 'product_category',
            slugColumn: 'slug',
            descriptionColumn: 'description',
            parentColumn: 'parent_id',
        );
        if ($productCategoryMap !== []) {
            Taxonomy::rebuildNestedSet('product_category');
        }

        $this->attachPivot(
            pivotTable: 'category_product',
            taxonomyMap: $productCategoryMap,
            taxonomyType: 'product_category',
            modelClass: Product::class,
            modelColumn: 'product_id',
            categoryColumn: 'product_category_id',
            now: $now,
        );

        $knowledgeCategoryMap = $this->importHierarchicalCategory(
            table: 'knowledge_categories',
            type: 'knowledge_category',
            slugColumn: 'slug',
            descriptionColumn: 'description',
            parentColumn: 'parent_id',
            metaColumns: ['visibility', 'position'],
        );
        if ($knowledgeCategoryMap !== []) {
            Taxonomy::rebuildNestedSet('knowledge_category');
        }

        $knowledgeTagMap = $this->importSimpleCategory(
            table: 'knowledge_tags',
            type: 'knowledge_tag',
            slugColumn: 'slug',
            descriptionColumn: 'description',
        );
        if ($knowledgeTagMap !== []) {
            Taxonomy::rebuildNestedSet('knowledge_tag');
        }

        $this->attachKnowledgeArticles($knowledgeCategoryMap, $knowledgeTagMap, $now);
    }

    /**
     * @return array<int, int> map of legacy id to taxonomy id
     */
    private function importSimpleCategory(string $table, string $type, ?string $slugColumn = 'slug', ?string $descriptionColumn = 'description', array $metaColumns = []): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $map = [];

        $records = DB::table($table)->get();

        foreach ($records as $record) {
            $teamId = $record->team_id ?? null;

            if ($teamId === null) {
                continue;
            }

            $slugSource = $slugColumn && isset($record->{$slugColumn}) ? (string) $record->{$slugColumn} : (string) $record->name;
            $slug = $this->uniqueSlug($slugSource, $type, $teamId);

            $meta = [];
            foreach ($metaColumns as $column) {
                if (isset($record->{$column})) {
                    $meta[$column] = $record->{$column};
                }
            }

            $taxonomy = Taxonomy::query()->create([
                'team_id' => $teamId,
                'name' => $record->name,
                'slug' => $slug,
                'type' => $type,
                'description' => $descriptionColumn && isset($record->{$descriptionColumn}) ? $record->{$descriptionColumn} : null,
                'parent_id' => null,
                'sort_order' => 0,
                'meta' => $meta === [] ? null : $meta,
                'created_at' => $record->created_at ?? now(),
                'updated_at' => $record->updated_at ?? now(),
            ]);

            $map[(int) $record->id] = (int) $taxonomy->getKey();
        }

        return $map;
    }

    /**
     * @return array<int, int> map of legacy id to taxonomy id
     */
    private function importHierarchicalCategory(string $table, string $type, ?string $slugColumn, ?string $descriptionColumn, string $parentColumn, array $metaColumns = []): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $records = DB::table($table)->get()->keyBy('id');

        $map = [];
        $pending = $records->keys()->all();

        // Simple top-down creation to preserve parent mapping
        while ($pending !== []) {
            $progressed = false;

            foreach ($pending as $index => $id) {
                $record = $records[$id];
                $parentId = $record->{$parentColumn} ?? null;

                if ($parentId !== null && ! array_key_exists((int) $parentId, $map)) {
                    continue;
                }

                $teamId = $record->team_id ?? null;
                if ($teamId === null) {
                    unset($pending[$index]);

                    continue;
                }

                $slugSource = $slugColumn && isset($record->{$slugColumn}) ? (string) $record->{$slugColumn} : (string) $record->name;
                $slug = $this->uniqueSlug($slugSource, $type, $teamId);

                $meta = [];
                foreach ($metaColumns as $column) {
                    if (isset($record->{$column})) {
                        $meta[$column] = $record->{$column};
                    }
                }

                $taxonomy = Taxonomy::query()->create([
                    'team_id' => $teamId,
                    'name' => $record->name,
                    'slug' => $slug,
                    'type' => $type,
                    'description' => $descriptionColumn && isset($record->{$descriptionColumn}) ? $record->{$descriptionColumn} : null,
                    'parent_id' => $parentId !== null ? ($map[(int) $parentId] ?? null) : null,
                    'sort_order' => $record->position ?? 0,
                    'meta' => $meta === [] ? null : $meta,
                    'created_at' => $record->created_at ?? now(),
                    'updated_at' => $record->updated_at ?? now(),
                ]);

                $map[(int) $id] = (int) $taxonomy->getKey();
                unset($pending[$index]);
                $progressed = true;
            }

            if (! $progressed) {
                break; // circular or missing parents; stop to avoid infinite loop
            }
        }

        return $map;
    }

    private function attachPivot(string $pivotTable, array $taxonomyMap, string $taxonomyType, string $modelClass, string $modelColumn, string $categoryColumn, \Illuminate\Support\Carbon $now): void
    {
        if (! Schema::hasTable($pivotTable) || $taxonomyMap === []) {
            return;
        }

        $rows = DB::table($pivotTable)->get();

        foreach ($rows as $row) {
            $taxonomyId = $taxonomyMap[(int) $row->{$categoryColumn}] ?? null;
            if ($taxonomyId === null) {
                continue;
            }

            DB::table('taxonomables')->updateOrInsert(
                [
                    'taxonomy_id' => $taxonomyId,
                    'taxonomable_type' => $modelClass,
                    'taxonomable_id' => $row->{$modelColumn},
                ],
                [
                    'created_at' => $row->created_at ?? $now,
                    'updated_at' => $row->updated_at ?? $now,
                ],
            );
        }
    }

    private function attachKnowledgeArticles(array $categoryMap, array $tagMap, \Illuminate\Support\Carbon $now): void
    {
        if (! Schema::hasTable('knowledge_articles')) {
            return;
        }

        $articles = DB::table('knowledge_articles')->get();

        foreach ($articles as $article) {
            $categoryId = $article->category_id ?? null;
            if ($categoryId !== null && isset($categoryMap[(int) $categoryId])) {
                DB::table('taxonomables')->updateOrInsert(
                    [
                        'taxonomy_id' => $categoryMap[(int) $categoryId],
                        'taxonomable_type' => KnowledgeArticle::class,
                        'taxonomable_id' => $article->id,
                    ],
                    [
                        'created_at' => $article->created_at ?? $now,
                        'updated_at' => $article->updated_at ?? $now,
                    ],
                );
            }
        }

        if (Schema::hasTable('knowledge_article_tag') && $tagMap !== []) {
            $links = DB::table('knowledge_article_tag')->get();

            foreach ($links as $link) {
                $taxonomyId = $tagMap[(int) $link->tag_id] ?? null;
                if ($taxonomyId === null) {
                    continue;
                }

                DB::table('taxonomables')->updateOrInsert(
                    [
                        'taxonomy_id' => $taxonomyId,
                        'taxonomable_type' => KnowledgeArticle::class,
                        'taxonomable_id' => $link->article_id,
                    ],
                    [
                        'created_at' => $link->created_at ?? $now,
                        'updated_at' => $link->updated_at ?? $now,
                    ],
                );
            }
        }
    }

    private function uniqueSlug(string $name, string $type, int $teamId): string
    {
        $base = Str::slug($name) ?: Str::slug($type . '-' . $teamId);
        $slug = $base;
        $counter = 1;

        while (DB::table('taxonomies')->where([
            'slug' => $slug,
            'type' => $type,
            'team_id' => $teamId,
        ])->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
};
