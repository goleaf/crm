<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\KnowledgeTag;

final readonly class KnowledgeTagObserver
{
    public function creating(KnowledgeTag $tag): void
    {
        if (auth('web')->check()) {
            $tag->creator_id ??= auth('web')->id();
            $tag->team_id ??= auth('web')->user()->currentTeam?->getKey();
        }
    }
}
