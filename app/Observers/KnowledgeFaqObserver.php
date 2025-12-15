<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\KnowledgeFaq;

final readonly class KnowledgeFaqObserver
{
    public function creating(KnowledgeFaq $faq): void
    {
        if ($faq->article !== null) {
            $faq->team_id ??= $faq->article->team_id;
        }

        if (auth('web')->check()) {
            $faq->creator_id ??= auth('web')->id();
            $faq->team_id ??= auth('web')->user()->currentTeam?->getKey();
        }
    }
}
