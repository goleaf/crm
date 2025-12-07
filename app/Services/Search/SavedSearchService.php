<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Models\SavedSearch;
use App\Models\User;
use App\Services\Tenancy\CurrentTeamResolver;
use Illuminate\Support\Collection;

final class SavedSearchService
{
    /**
     * Persist or update a saved search for the current team.
     *
     * @param  array<string, mixed>  $filters
     */
    public function save(User $user, string $name, string $resource, ?string $query, array $filters = []): SavedSearch
    {
        $teamId = CurrentTeamResolver::resolveId($user);

        return SavedSearch::query()->updateOrCreate(
            [
                'team_id' => $teamId,
                'user_id' => $user->getKey(),
                'name' => $name,
                'resource' => $resource,
            ],
            [
                'query' => $query,
                'filters' => $filters,
            ]
        );
    }

    /**
     * List saved searches for the current team.
     *
     * @return Collection<int, SavedSearch>
     */
    public function list(User $user, ?string $resource = null): Collection
    {
        $teamId = CurrentTeamResolver::resolveId($user);

        return SavedSearch::query()
            ->where('team_id', $teamId)
            ->where('user_id', $user->getKey())
            ->when($resource, fn ($query) => $query->where('resource', $resource))
            ->orderByDesc('updated_at')
            ->get();
    }
}
