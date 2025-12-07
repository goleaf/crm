<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\KnowledgeCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final readonly class KnowledgeCategoryObserver
{
    public function creating(KnowledgeCategory $category): void
    {
        if (auth('web')->check()) {
            $category->creator_id ??= auth('web')->id();
            $category->team_id ??= auth('web')->user()->currentTeam?->getKey();
        }

        $category->slug = $this->uniqueSlug($category);
    }

    public function updating(KnowledgeCategory $category): void
    {
        if ($category->isDirty('name') || $category->isDirty('slug')) {
            $category->slug = $this->uniqueSlug($category);
        }
    }

    private function uniqueSlug(KnowledgeCategory $category): string
    {
        $base = Str::slug($category->slug ?: $category->name) ?: Str::random(8);
        $slug = $base;
        $counter = 1;

        while (
            KnowledgeCategory::query()
                ->where('team_id', $category->team_id)
                ->when($category->exists, fn (Builder $query) => $query->whereKeyNot($category->getKey()))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
