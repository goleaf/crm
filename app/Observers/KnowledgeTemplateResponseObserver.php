<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\KnowledgeTemplateResponse;

final readonly class KnowledgeTemplateResponseObserver
{
    public function creating(KnowledgeTemplateResponse $template): void
    {
        if (auth('web')->check()) {
            $template->creator_id ??= auth('web')->id();
            $template->team_id ??= auth('web')->user()->currentTeam?->getKey();
        }
    }
}
