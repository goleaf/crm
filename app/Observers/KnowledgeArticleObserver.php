<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\Knowledge\ArticleStatus;
use App\Models\KnowledgeArticle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final readonly class KnowledgeArticleObserver
{
    public function creating(KnowledgeArticle $article): void
    {
        if (auth('web')->check()) {
            $article->creator_id ??= auth('web')->id();
            $article->author_id ??= auth('web')->id();
            $article->team_id ??= auth('web')->user()->currentTeam?->getKey();
        }

        $article->slug = $this->uniqueSlug($article);
    }

    public function updating(KnowledgeArticle $article): void
    {
        if ($article->isDirty('title') || $article->isDirty('slug')) {
            $article->slug = $this->uniqueSlug($article);
        }

        if ($article->isDirty('status') && $article->status === ArticleStatus::ARCHIVED && $article->archived_at === null) {
            $article->archived_at = now();
        }
    }

    public function saved(KnowledgeArticle $article): void
    {
        if ($article->status === ArticleStatus::PUBLISHED) {
            $this->persistVersion($article);
        }

        if ($article->status !== ArticleStatus::ARCHIVED && $article->archived_at !== null) {
            $article->archived_at = null;
            $article->saveQuietly();
        }
    }

    private function persistVersion(KnowledgeArticle $article): void
    {
        $trackedFields = [
            'title',
            'slug',
            'summary',
            'content',
            'meta_title',
            'meta_description',
            'meta_keywords',
            'visibility',
        ];

        $statusChangedToPublished = $article->wasChanged('status') && $article->status === ArticleStatus::PUBLISHED;
        $hasContentChanges = $article->wasRecentlyCreated || $statusChangedToPublished || $article->wasChanged($trackedFields);

        if (! $hasContentChanges && $article->current_version_id !== null) {
            return;
        }

        $nextVersion = (int) $article->versions()->max('version') + 1;
        $publishedAt = $article->published_at ?? now();

        $version = $article->versions()->create([
            'team_id' => $article->team_id,
            'editor_id' => auth('web')->id() ?? $article->author_id ?? $article->creator_id,
            'approver_id' => $article->approver_id,
            'version' => $nextVersion,
            'status' => ArticleStatus::PUBLISHED,
            'visibility' => $article->visibility,
            'title' => $article->title,
            'slug' => $article->slug,
            'summary' => $article->summary,
            'content' => $article->content,
            'meta_title' => $article->meta_title,
            'meta_description' => $article->meta_description,
            'meta_keywords' => $article->meta_keywords,
            'change_notes' => $article->approval_notes,
            'published_at' => $publishedAt,
        ]);

        $article->published_at = $publishedAt;
        $article->current_version_id = $version->getKey();
        $article->saveQuietly();
    }

    private function uniqueSlug(KnowledgeArticle $article): string
    {
        $base = Str::slug($article->slug ?: $article->title) ?: Str::random(8);
        $slug = $base;
        $counter = 1;

        while (
            KnowledgeArticle::query()
                ->where('team_id', $article->team_id)
                ->when($article->exists, fn (Builder $query) => $query->whereKeyNot($article->getKey()))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
