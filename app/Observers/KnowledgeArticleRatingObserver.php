<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\KnowledgeArticleRating;

final readonly class KnowledgeArticleRatingObserver
{
    public function creating(KnowledgeArticleRating $rating): void
    {
        if ($rating->article !== null) {
            $rating->team_id ??= $rating->article->team_id;
        }

        if (auth('web')->check()) {
            $rating->user_id ??= auth('web')->id();
            $rating->team_id ??= auth('web')->user()->currentTeam?->getKey();
        }

        $rating->rating = max(1, min(5, (int) $rating->rating));
    }

    public function created(KnowledgeArticleRating $rating): void
    {
        if ($rating->article === null) {
            return;
        }

        $field = $rating->rating >= 4 ? 'helpful_count' : 'not_helpful_count';
        $rating->article->increment($field);
    }
}
