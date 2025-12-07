<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\KnowledgeTag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final readonly class KnowledgeTagObserver
{
    public function creating(KnowledgeTag $tag): void
    {
        if (auth('web')->check()) {
            $tag->creator_id ??= auth('web')->id();
            $tag->team_id ??= auth('web')->user()->currentTeam?->getKey();
        }

        $tag->slug = $this->uniqueSlug($tag);
    }

    public function updating(KnowledgeTag $tag): void
    {
        if ($tag->isDirty('name') || $tag->isDirty('slug')) {
            $tag->slug = $this->uniqueSlug($tag);
        }
    }

    private function uniqueSlug(KnowledgeTag $tag): string
    {
        $base = Str::slug($tag->slug ?: $tag->name) ?: Str::random(8);
        $slug = $base;
        $counter = 1;

        while (
            KnowledgeTag::query()
                ->where('team_id', $tag->team_id)
                ->when($tag->exists, fn (Builder $query) => $query->whereKeyNot($tag->getKey()))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
