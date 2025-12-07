<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\SupportCase;
use Illuminate\Support\Str;

final readonly class SupportCaseObserver
{
    public function creating(SupportCase $case): void
    {
        if (auth('web')->check()) {
            $case->creator_id = auth('web')->id();
            $case->team_id = auth('web')->user()->currentTeam?->getKey();

            if ($case->assigned_team_id === null) {
                $case->assigned_team_id = auth('web')->user()->currentTeam?->getKey();
            }
        }

        if (blank($case->case_number)) {
            $case->case_number = 'CASE-'.Str::upper(Str::ulid());
        }
    }
}
