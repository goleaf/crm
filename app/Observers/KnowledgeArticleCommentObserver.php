<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\KnowledgeArticleComment;

final readonly class KnowledgeArticleCommentObserver
{
    public function creating(KnowledgeArticleComment $comment): void
    {
        if ($comment->article !== null) {
            $comment->team_id ??= $comment->article->team_id;
        }

        if (auth('web')->check()) {
            $comment->author_id ??= auth('web')->id();
            $comment->team_id ??= auth('web')->user()->currentTeam?->getKey();
        }
    }
}
