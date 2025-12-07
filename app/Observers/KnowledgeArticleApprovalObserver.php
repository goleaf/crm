<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\Knowledge\ApprovalStatus;
use App\Enums\Knowledge\ArticleStatus;
use App\Models\KnowledgeArticleApproval;

final readonly class KnowledgeArticleApprovalObserver
{
    public function creating(KnowledgeArticleApproval $approval): void
    {
        if ($approval->article !== null) {
            $approval->team_id ??= $approval->article->team_id;
        }

        if (auth('web')->check()) {
            $approval->requested_by_id ??= auth('web')->id();
            $approval->team_id ??= auth('web')->user()->currentTeam?->getKey();
        }
    }

    public function saving(KnowledgeArticleApproval $approval): void
    {
        if ($approval->status !== ApprovalStatus::PENDING && $approval->decided_at === null) {
            $approval->decided_at = now();
        }
    }

    public function saved(KnowledgeArticleApproval $approval): void
    {
        $article = $approval->article;

        if ($article === null) {
            return;
        }

        if ($approval->status === ApprovalStatus::APPROVED) {
            $article->status = ArticleStatus::PUBLISHED;
            $article->approver_id = $approval->approver_id;
            $article->approval_notes = $approval->decision_notes;
            $article->published_at ??= now();
            $article->save();
        } elseif ($approval->status === ApprovalStatus::REJECTED) {
            $article->status = ArticleStatus::DRAFT;
            $article->approval_notes = $approval->decision_notes;
            $article->save();
        }
    }
}
