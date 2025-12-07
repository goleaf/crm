<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\KnowledgeArticleRelation;

final readonly class KnowledgeArticleRelationObserver
{
    public function creating(KnowledgeArticleRelation $relation): void
    {
        if ($relation->article !== null) {
            $relation->team_id ??= $relation->article->team_id;
        }

        if (auth('web')->check()) {
            $relation->team_id ??= auth('web')->user()->currentTeam?->getKey();
        }

        if ($relation->article_id === $relation->related_article_id) {
            throw new \InvalidArgumentException('An article cannot be related to itself.');
        }
    }
}
