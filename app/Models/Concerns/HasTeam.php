<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Scopes\TeamScope;
use App\Models\Team;
use App\Services\Tenancy\CurrentTeamResolver;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait HasTeam
{
    protected static function bootHasTeam(): void
    {
        static::addGlobalScope(new TeamScope);

        static::creating(function (EloquentModel $model): void {
            if ($model->getAttribute('team_id') !== null) {
                return;
            }

            $teamId = CurrentTeamResolver::resolveId();

            if ($teamId === null) {
                $user = Auth::guard('web')->user() ?? Auth::user();
                $teamId = $user?->current_team_id ?? $user?->personalTeam()?->getKey();
            }

            if ($teamId !== null) {
                $model->setAttribute('team_id', $teamId);
            }
        });
    }

    public function save(array $options = []): bool
    {
        if ($this->getAttribute('team_id') === null) {
            $teamId = CurrentTeamResolver::resolveId();

            if ($teamId === null) {
                $user = Auth::guard('web')->user() ?? Auth::user();
                $teamId = $user?->current_team_id ?? $user?->personalTeam()?->getKey();
            }

            if ($teamId !== null) {
                $this->setAttribute('team_id', $teamId);
            }
        }

        return parent::save($options);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
