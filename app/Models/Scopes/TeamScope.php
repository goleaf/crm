<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Services\Tenancy\CurrentTeamResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * @template TModel of Model
 */
final class TeamScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  Builder<TModel>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $team = CurrentTeamResolver::resolve();

        if ($team instanceof \App\Models\Team) {
            $builder->whereBelongsTo($team, 'team');
        }
    }
}
