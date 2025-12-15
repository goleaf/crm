<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy as BaseTaxonomy;
use Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy;
use App\Models\Taxonomy;
use App\Services\Tenancy\CurrentTeamResolver;

/**
 * Wrapper around the package trait that filters attached taxonomies to the current team.
 */
trait HasTaxonomies
{
    use HasTaxonomy {
        getTaxonomyIds as baseGetTaxonomyIds;
    }

    /**
     * @param int|string|array<int, int|string|BaseTaxonomy>|BaseTaxonomy|\Illuminate\Support\Collection<int, BaseTaxonomy>|null $taxonomies
     *
     * @return array<int, int|string>
     */
    protected function getTaxonomyIds($taxonomies): array
    {
        $candidateIds = $this->baseGetTaxonomyIds($taxonomies);

        if ($candidateIds === []) {
            return [];
        }

        /** @var class-string<BaseTaxonomy> $model */
        $model = config('taxonomy.model', Taxonomy::class);

        $teamId = CurrentTeamResolver::resolveId();

        return $model::query()
            ->whereIn('id', $candidateIds)
            ->when($teamId !== null && $model === Taxonomy::class, fn ($query) => $query->where('team_id', $teamId))
            ->pluck('id')
            ->all();
    }
}
