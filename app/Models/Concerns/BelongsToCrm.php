<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCrm
{
    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope records for a specific tenant/team.
     */
    public function scopeForCrmTenant(Builder $query, Team $team): Builder
    {
        return $query->where('team_id', $team->getKey());
    }
}
