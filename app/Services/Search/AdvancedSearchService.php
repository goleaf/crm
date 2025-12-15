<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\SearchHistory;
use App\Models\SupportCase;
use App\Models\Task;
use App\Services\Tenancy\CurrentTeamResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class AdvancedSearchService
{
    public function __construct(
        private SearchIndexService $searchIndex,
        private int $defaultPerPage = 25,
    ) {}

    /**
     * Perform advanced search with filters, operators, and ranking.
     *
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $options
     */
    public function search(
        string $query,
        ?string $module = null,
        array $filters = [],
        array $options = [],
        ?int $perPage = null,
    ): LengthAwarePaginator {
        $startTime = microtime(true);
        $perPage ??= $this->defaultPerPage;
        $teamId = CurrentTeamResolver::resolveId();

        // Build search results
        $results = $this->buildSearchQuery($query, $module, $filters, $teamId);

        // Apply sorting and pagination
        $results = $this->applySorting($results, $options['sort'] ?? 'relevance');
        $paginatedResults = $results->paginate($perPage);

        // Record search history
        $this->recordSearchHistory($query, $module, $filters, $paginatedResults->total(), microtime(true) - $startTime);

        return $paginatedResults;
    }

    /**
     * Get search suggestions based on query and context.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getSuggestions(string $query, ?string $module = null, int $limit = 10): Collection
    {
        $teamId = CurrentTeamResolver::resolveId();

        // Get suggestions from search index
        $indexSuggestions = $this->searchIndex->getSuggestions($query, $module, $teamId, $limit);

        // Get suggestions from search history
        $historySuggestions = $this->getHistorySuggestions($query, $module, $limit);

        // Combine and rank suggestions
        return $indexSuggestions->merge($historySuggestions)
            ->unique()
            ->take($limit)
            ->map(fn ($term): array => [
                'term' => $term,
                'type' => 'suggestion',
                'module' => $module,
            ]);
    }

    /**
     * Get available search operators.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAvailableOperators(): array
    {
        return [
            'equals' => [
                'label' => 'Equals',
                'symbol' => '=',
                'description' => 'Exact match',
                'types' => ['text', 'number', 'date', 'select'],
            ],
            'not_equals' => [
                'label' => 'Not equals',
                'symbol' => '!=',
                'description' => 'Does not equal',
                'types' => ['text', 'number', 'date', 'select'],
            ],
            'contains' => [
                'label' => 'Contains',
                'symbol' => 'LIKE',
                'description' => 'Contains text',
                'types' => ['text'],
            ],
            'not_contains' => [
                'label' => 'Does not contain',
                'symbol' => 'NOT LIKE',
                'description' => 'Does not contain text',
                'types' => ['text'],
            ],
            'starts_with' => [
                'label' => 'Starts with',
                'symbol' => 'LIKE',
                'description' => 'Starts with text',
                'types' => ['text'],
            ],
            'ends_with' => [
                'label' => 'Ends with',
                'symbol' => 'LIKE',
                'description' => 'Ends with text',
                'types' => ['text'],
            ],
            'greater_than' => [
                'label' => 'Greater than',
                'symbol' => '>',
                'description' => 'Greater than value',
                'types' => ['number', 'date'],
            ],
            'greater_than_or_equal' => [
                'label' => 'Greater than or equal',
                'symbol' => '>=',
                'description' => 'Greater than or equal to value',
                'types' => ['number', 'date'],
            ],
            'less_than' => [
                'label' => 'Less than',
                'symbol' => '<',
                'description' => 'Less than value',
                'types' => ['number', 'date'],
            ],
            'less_than_or_equal' => [
                'label' => 'Less than or equal',
                'symbol' => '<=',
                'description' => 'Less than or equal to value',
                'types' => ['number', 'date'],
            ],
            'in' => [
                'label' => 'In list',
                'symbol' => 'IN',
                'description' => 'Value is in list',
                'types' => ['select', 'multiselect'],
            ],
            'not_in' => [
                'label' => 'Not in list',
                'symbol' => 'NOT IN',
                'description' => 'Value is not in list',
                'types' => ['select', 'multiselect'],
            ],
            'is_null' => [
                'label' => 'Is empty',
                'symbol' => 'IS NULL',
                'description' => 'Field is empty',
                'types' => ['text', 'number', 'date', 'select'],
            ],
            'is_not_null' => [
                'label' => 'Is not empty',
                'symbol' => 'IS NOT NULL',
                'description' => 'Field is not empty',
                'types' => ['text', 'number', 'date', 'select'],
            ],
        ];
    }

    /**
     * Get searchable modules and their fields.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getSearchableModules(): array
    {
        return [
            'companies' => [
                'label' => 'Companies',
                'model' => Company::class,
                'fields' => [
                    'name' => ['type' => 'text', 'label' => 'Name'],
                    'website' => ['type' => 'text', 'label' => 'Website'],
                    'primary_email' => ['type' => 'text', 'label' => 'Email'],
                    'phone' => ['type' => 'text', 'label' => 'Phone'],
                    'industry' => ['type' => 'select', 'label' => 'Industry'],
                    'created_at' => ['type' => 'date', 'label' => 'Created Date'],
                ],
            ],
            'people' => [
                'label' => 'People',
                'model' => People::class,
                'fields' => [
                    'name' => ['type' => 'text', 'label' => 'Name'],
                    'primary_email' => ['type' => 'text', 'label' => 'Email'],
                    'phone_mobile' => ['type' => 'text', 'label' => 'Mobile Phone'],
                    'job_title' => ['type' => 'text', 'label' => 'Job Title'],
                    'department' => ['type' => 'text', 'label' => 'Department'],
                    'created_at' => ['type' => 'date', 'label' => 'Created Date'],
                ],
            ],
            'opportunities' => [
                'label' => 'Opportunities',
                'model' => Opportunity::class,
                'fields' => [
                    'name' => ['type' => 'text', 'label' => 'Name'],
                    'value' => ['type' => 'number', 'label' => 'Value'],
                    'stage' => ['type' => 'select', 'label' => 'Stage'],
                    'close_date' => ['type' => 'date', 'label' => 'Close Date'],
                    'created_at' => ['type' => 'date', 'label' => 'Created Date'],
                ],
            ],
            'tasks' => [
                'label' => 'Tasks',
                'model' => Task::class,
                'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Title'],
                    'description' => ['type' => 'text', 'label' => 'Description'],
                    'status' => ['type' => 'select', 'label' => 'Status'],
                    'due_date' => ['type' => 'date', 'label' => 'Due Date'],
                    'created_at' => ['type' => 'date', 'label' => 'Created Date'],
                ],
            ],
            'support_cases' => [
                'label' => 'Support Cases',
                'model' => SupportCase::class,
                'fields' => [
                    'subject' => ['type' => 'text', 'label' => 'Subject'],
                    'description' => ['type' => 'text', 'label' => 'Description'],
                    'status' => ['type' => 'select', 'label' => 'Status'],
                    'priority' => ['type' => 'select', 'label' => 'Priority'],
                    'created_at' => ['type' => 'date', 'label' => 'Created Date'],
                ],
            ],
        ];
    }

    /**
     * Build search query with filters and options.
     */
    private function buildSearchQuery(
        string $query,
        ?string $module,
        array $filters,
        ?int $teamId,
    ): Builder {
        $modules = $module ? [$module] : array_keys($this->getSearchableModules());
        $unionQueries = [];

        foreach ($modules as $moduleKey) {
            $moduleConfig = $this->getSearchableModules()[$moduleKey] ?? null;
            if (! $moduleConfig) {
                continue;
            }

            $modelClass = $moduleConfig['model'];
            $builder = $modelClass::query();

            // Apply team scoping
            if ($teamId && method_exists($modelClass, 'scopeForTeam')) {
                $builder->forTeam($teamId);
            } elseif ($teamId && in_array('team_id', (new $modelClass)->getFillable())) {
                $builder->where('team_id', $teamId);
            }

            // Apply text search
            if ($query !== '' && $query !== '0') {
                $this->applyTextSearch($builder, $query, $moduleConfig['fields']);
            }

            // Apply filters
            $this->applyFilters($builder, $filters, $moduleConfig['fields']);

            // Add module identifier
            $builder->addSelect(DB::raw("'{$moduleKey}' as search_module"));
            $builder->addSelect(DB::raw("'{$modelClass}' as search_type"));

            $unionQueries[] = $builder;
        }

        // Combine queries with UNION
        if ($unionQueries === []) {
            return Company::query()->whereRaw('1 = 0'); // Empty result
        }

        $baseQuery = array_shift($unionQueries);
        foreach ($unionQueries as $unionQuery) {
            $baseQuery->union($unionQuery);
        }

        return $baseQuery;
    }

    /**
     * Apply text search to query builder.
     */
    private function applyTextSearch(Builder $builder, string $query, array $fields): void
    {
        $tokens = $this->tokenize($query);
        if ($tokens === []) {
            return;
        }

        $builder->where(function (Builder $q) use ($tokens, $fields): void {
            foreach ($tokens as $token) {
                $q->where(function (Builder $inner) use ($token, $fields): void {
                    foreach ($fields as $field => $config) {
                        if ($config['type'] === 'text') {
                            $inner->orWhere($field, 'like', "%{$token}%");
                        }
                    }
                });
            }
        });
    }

    /**
     * Apply filters to query builder.
     *
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $fields
     */
    private function applyFilters(Builder $builder, array $filters, array $fields): void
    {
        foreach ($filters as $filter) {
            $field = $filter['field'] ?? null;
            $operator = $filter['operator'] ?? 'equals';
            $value = $filter['value'] ?? null;
            if (! $field) {
                continue;
            }
            if (! isset($fields[$field])) {
                continue;
            }

            $this->applyFilter($builder, $field, $operator, $value);
        }
    }

    /**
     * Apply a single filter to query builder.
     */
    private function applyFilter(Builder $builder, string $field, string $operator, mixed $value): void
    {
        match ($operator) {
            'equals' => $builder->where($field, '=', $value),
            'not_equals' => $builder->where($field, '!=', $value),
            'contains' => $builder->where($field, 'like', "%{$value}%"),
            'not_contains' => $builder->where($field, 'not like', "%{$value}%"),
            'starts_with' => $builder->where($field, 'like', "{$value}%"),
            'ends_with' => $builder->where($field, 'like', "%{$value}"),
            'greater_than' => $builder->where($field, '>', $value),
            'greater_than_or_equal' => $builder->where($field, '>=', $value),
            'less_than' => $builder->where($field, '<', $value),
            'less_than_or_equal' => $builder->where($field, '<=', $value),
            'in' => $builder->whereIn($field, (array) $value),
            'not_in' => $builder->whereNotIn($field, (array) $value),
            'is_null' => $builder->whereNull($field),
            'is_not_null' => $builder->whereNotNull($field),
            default => $builder->where($field, '=', $value),
        };
    }

    /**
     * Apply sorting to query results.
     */
    private function applySorting(Builder $builder, string $sort): Builder
    {
        return match ($sort) {
            'relevance' => $builder->orderByRaw('CASE WHEN search_module = ? THEN 1 ELSE 2 END', ['companies'])->latest(),
            'date_desc' => $builder->latest(),
            'date_asc' => $builder->oldest(),
            'name_asc' => $builder->orderBy('name', 'asc'),
            'name_desc' => $builder->orderBy('name', 'desc'),
            default => $builder->latest(),
        };
    }

    /**
     * Get search suggestions from history.
     *
     * @return Collection<int, string>
     */
    private function getHistorySuggestions(string $query, ?string $module, int $limit): Collection
    {
        $teamId = CurrentTeamResolver::resolveId();

        return SearchHistory::query()
            ->when($teamId, fn ($q) => $q->where('team_id', $teamId))
            ->when($module, fn ($q) => $q->where('module', $module))
            ->where('query', 'like', "%{$query}%")
            ->where('results_count', '>', 0)
            ->latest('searched_at')
            ->limit($limit)
            ->pluck('query')
            ->unique()
            ->values();
    }

    /**
     * Record search in history.
     */
    private function recordSearchHistory(
        string $query,
        ?string $module,
        array $filters,
        int $resultsCount,
        float $executionTime,
    ): void {
        $teamId = CurrentTeamResolver::resolveId();
        $userId = Auth::id();

        if (! $teamId || ! $userId) {
            return;
        }

        SearchHistory::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'query' => $query,
            'module' => $module,
            'filters' => $filters,
            'results_count' => $resultsCount,
            'execution_time' => $executionTime,
            'searched_at' => now(),
        ]);
    }

    /**
     * Tokenize search query.
     *
     * @return array<string>
     */
    private function tokenize(string $query): array
    {
        // Handle quoted phrases
        $phrases = [];
        $query = preg_replace_callback('/"([^"]+)"/', function (array $matches) use (&$phrases): string {
            $phrases[] = $matches[1];

            return '__PHRASE_' . (count($phrases) - 1) . '__';
        }, $query);

        // Split by whitespace
        $tokens = preg_split('/\s+/', trim((string) $query));

        // Replace phrase placeholders
        $tokens = array_map(function ($token) use ($phrases): string {
            if (preg_match('/__PHRASE_(\d+)__/', $token, $matches)) {
                return $phrases[(int) $matches[1]];
            }

            return $token;
        }, $tokens);

        return array_filter($tokens);
    }
}
