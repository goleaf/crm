<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\KnowledgeCategory;

final readonly class KnowledgeCategoryObserver
{
    public function creating(KnowledgeCategory $category): void
    {
        if (auth('web')->check()) {
            $category->creator_id ??= auth('web')->id();
            $category->team_id ??= auth('web')->user()->currentTeam?->getKey();
        }
    }
}
