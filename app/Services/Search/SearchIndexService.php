<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Models\Model;
use App\Models\SearchIndex;
use App\Services\Tenancy\CurrentTeamResolver;
use Illuminate\Support\Collection;

final class SearchIndexService
{
    /**
     * Index a model for search.
     */
    public function indexModel(Model $model, string $module, array $searchableFields = []): void
    {
        $teamId = $this->getTeamId($model);

        // Extract searchable content from the model
        $terms = $this->extractSearchTerms($model, $searchableFields);

        foreach ($terms as $term) {
            SearchIndex::query()->updateOrCreate(
                [
                    'team_id' => $teamId,
                    'term' => $term,
                    'module' => $module,
                    'searchable_type' => $model::class,
                    'searchable_id' => $model->getKey(),
                ],
                [
                    'metadata' => $this->buildMetadata($model),
                    'ranking_score' => 1.0,
                    'search_count' => 0,
                ],
            );
        }
    }

    /**
     * Remove model from search index.
     */
    public function removeModel(Model $model): void
    {
        SearchIndex::query()
            ->where('searchable_type', $model::class)
            ->where('searchable_id', $model->getKey())
            ->delete();
    }

    /**
     * Update search index for a model.
     */
    public function updateModel(Model $model, string $module, array $searchableFields = []): void
    {
        $this->removeModel($model);
        $this->indexModel($model, $module, $searchableFields);
    }

    /**
     * Search the index for terms.
     *
     * @return Collection<int, SearchIndex>
     */
    public function search(string $query, ?string $module = null, ?int $teamId = null, int $limit = 50): Collection
    {
        $teamId ??= CurrentTeamResolver::resolveId();
        $terms = $this->tokenize($query);

        $builder = SearchIndex::query()
            ->when($teamId, fn ($q) => $q->where('team_id', $teamId))
            ->when($module, fn ($q) => $q->where('module', $module))
            ->with('searchable');

        if ($terms !== []) {
            $builder->where(function (\Illuminate\Contracts\Database\Query\Builder $q) use ($terms): void {
                foreach ($terms as $term) {
                    $q->orWhere('term', 'like', "%{$term}%");
                }
            });
        }

        $results = $builder
            ->orderByDesc('ranking_score')
            ->orderByDesc('search_count')
            ->limit($limit)
            ->get();

        // Record search activity
        $this->recordSearchActivity($results);

        return $results;
    }

    /**
     * Get search suggestions based on query.
     *
     * @return Collection<int, string>
     */
    public function getSuggestions(string $query, ?string $module = null, ?int $teamId = null, int $limit = 10): Collection
    {
        $teamId ??= CurrentTeamResolver::resolveId();

        return SearchIndex::query()
            ->when($teamId, fn ($q) => $q->where('team_id', $teamId))
            ->when($module, fn ($q) => $q->where('module', $module))
            ->where('term', 'like', "%{$query}%")
            ->orderByDesc('ranking_score')
            ->orderByDesc('search_count')
            ->limit($limit)
            ->pluck('term')
            ->unique()
            ->values();
    }

    /**
     * Update ranking scores for all search indices.
     */
    public function updateRankingScores(?int $teamId = null): void
    {
        $teamId ??= CurrentTeamResolver::resolveId();

        SearchIndex::query()
            ->when($teamId, fn ($q) => $q->where('team_id', $teamId))
            ->chunk(1000, function ($indices): void {
                foreach ($indices as $index) {
                    $index->updateRankingScore();
                }
            });
    }

    /**
     * Extract search terms from a model.
     *
     * @return array<string>
     */
    private function extractSearchTerms(Model $model, array $searchableFields): array
    {
        $terms = [];

        foreach ($searchableFields as $field) {
            $value = data_get($model, $field);
            if (is_string($value)) {
                $terms = array_merge($terms, $this->tokenize($value));
            }
        }

        return array_unique(array_filter($terms));
    }

    /**
     * Build metadata for search index.
     *
     * @return array<string, mixed>
     */
    private function buildMetadata(Model $model): array
    {
        return [
            'created_at' => $model->created_at?->toISOString(),
            'updated_at' => $model->updated_at?->toISOString(),
            'class' => $model::class,
        ];
    }

    /**
     * Tokenize a string into search terms.
     *
     * @return array<string>
     */
    private function tokenize(string $text): array
    {
        // Remove special characters and split by whitespace
        $text = preg_replace('/[^\w\s]/', ' ', $text);
        $tokens = preg_split('/\s+/', strtolower(trim((string) $text)));

        // Filter out short words and common stop words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];

        return array_filter($tokens, fn ($token): bool => strlen((string) $token) >= 2 && ! in_array($token, $stopWords));
    }

    /**
     * Record search activity for ranking purposes.
     */
    private function recordSearchActivity(Collection $results): void
    {
        foreach ($results as $index) {
            $index->recordSearch();
        }
    }

    /**
     * Get team ID from model.
     */
    private function getTeamId(Model $model): ?int
    {
        if (method_exists($model, 'getTeamId')) {
            return $model->getTeamId();
        }

        return $model->team_id ?? CurrentTeamResolver::resolveId();
    }
}
