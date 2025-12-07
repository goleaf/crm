<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\SupportCase;
use App\Models\Task;
use App\Services\Tenancy\CurrentTeamResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

final class GlobalSearchService
{
    /**
     * Provide reusable quick filters that can be surfaced in the UI.
     *
     * @return array<string, array<string, mixed>>
     */
    public function quickFilters(): array
    {
        $userId = Auth::id();
        $recentThreshold = Carbon::now()->subDays(7);

        return [
            'recent' => [
                'label' => 'Recent activity',
                'filters' => [
                    'companies' => [
                        ['field' => 'created_at', 'operator' => '>=', 'value' => $recentThreshold],
                    ],
                    'people' => [
                        ['field' => 'created_at', 'operator' => '>=', 'value' => $recentThreshold],
                    ],
                    'opportunities' => [
                        ['field' => 'created_at', 'operator' => '>=', 'value' => $recentThreshold],
                    ],
                    'tasks' => [
                        ['field' => 'created_at', 'operator' => '>=', 'value' => $recentThreshold],
                    ],
                    'support_cases' => [
                        ['field' => 'created_at', 'operator' => '>=', 'value' => $recentThreshold],
                    ],
                ],
            ],
            'mine' => [
                'label' => 'Assigned to me',
                'filters' => [
                    'opportunities' => [
                        ['field' => 'creator_id', 'operator' => '=', 'value' => $userId],
                    ],
                    'tasks' => [
                        ['field' => 'user_id', 'operator' => '=', 'value' => $userId],
                    ],
                    'support_cases' => [
                        ['field' => 'assigned_to_id', 'operator' => '=', 'value' => $userId],
                    ],
                ],
            ],
        ];
    }

    /**
     * Execute a multi-entity search with optional per-entity filters.
     *
     * @param  array<string, array<int, array<string, mixed>>>  $filters
     * @return array<string, Collection<int, mixed>>
     */
    public function search(string $query, array $filters = [], int $limitPerEntity = 5): array
    {
        $tokens = $this->tokenize($query);
        $teamId = CurrentTeamResolver::resolveId();

        return [
            'companies' => $this->searchCompanies($tokens, $filters['companies'] ?? [], $teamId, $limitPerEntity),
            'people' => $this->searchPeople($tokens, $filters['people'] ?? [], $teamId, $limitPerEntity),
            'opportunities' => $this->searchOpportunities($tokens, $filters['opportunities'] ?? [], $teamId, $limitPerEntity),
            'tasks' => $this->searchTasks($tokens, $filters['tasks'] ?? [], $teamId, $limitPerEntity),
            'support_cases' => $this->searchSupportCases($tokens, $filters['support_cases'] ?? [], $teamId, $limitPerEntity),
        ];
    }

    /**
     * @param  list<string>  $tokens
     * @return Collection<int, Company>
     */
    private function searchCompanies(array $tokens, array $filters, ?int $teamId, int $limit): Collection
    {
        $builder = Company::query()
            ->when($teamId, fn (Builder $query): Builder => $query->where('team_id', $teamId));

        $this->applyTokens($builder, $tokens, ['name', 'website', 'primary_email', 'phone', 'industry']);
        $this->applyFilters($builder, $filters, ['industry', 'account_owner_id', 'creation_source', 'created_at']);

        return $builder->limit($limit)->get();
    }

    /**
     * @param  list<string>  $tokens
     * @return Collection<int, People>
     */
    private function searchPeople(array $tokens, array $filters, ?int $teamId, int $limit): Collection
    {
        $builder = People::query()
            ->when($teamId, fn (Builder $query): Builder => $query->where('team_id', $teamId));

        $this->applyTokens($builder, $tokens, ['name', 'primary_email', 'alternate_email', 'phone_mobile', 'phone_office', 'job_title', 'department']);
        $this->applyFilters($builder, $filters, ['company_id', 'lead_source', 'creation_source', 'created_at', 'creator_id']);

        return $builder->limit($limit)->get();
    }

    /**
     * @param  list<string>  $tokens
     * @return Collection<int, Opportunity>
     */
    private function searchOpportunities(array $tokens, array $filters, ?int $teamId, int $limit): Collection
    {
        $builder = Opportunity::query()
            ->when($teamId, fn (Builder $query): Builder => $query->where('team_id', $teamId));

        $this->applyTokens($builder, $tokens, ['name']);
        $this->applyFilters($builder, $filters, ['company_id', 'creator_id', 'creation_source', 'created_at']);

        return $builder->limit($limit)->get();
    }

    /**
     * @param  list<string>  $tokens
     * @return Collection<int, Task>
     */
    private function searchTasks(array $tokens, array $filters, ?int $teamId, int $limit): Collection
    {
        $builder = Task::query()
            ->when($teamId, fn (Builder $query): Builder => $query->where('team_id', $teamId));

        $this->applyTokens($builder, $tokens, ['title', 'description']);
        $this->applyFilters($builder, $filters, ['status', 'user_id', 'creation_source', 'created_at']);

        return $builder->limit($limit)->get();
    }

    /**
     * @param  list<string>  $tokens
     * @return Collection<int, SupportCase>
     */
    private function searchSupportCases(array $tokens, array $filters, ?int $teamId, int $limit): Collection
    {
        $builder = SupportCase::query()
            ->when($teamId, fn (Builder $query): Builder => $query->where('team_id', $teamId));

        $this->applyTokens($builder, $tokens, ['subject', 'description', 'ticket_number']);
        $this->applyFilters($builder, $filters, ['status', 'priority', 'assigned_to_id', 'created_at']);

        return $builder->limit($limit)->get();
    }

    /**
     * @param  list<string>  $tokens
     * @param  list<string>  $columns
     */
    private function applyTokens(Builder $builder, array $tokens, array $columns): void
    {
        if ($tokens === []) {
            return;
        }

        $builder->where(function (Builder $query) use ($tokens, $columns): void {
            foreach ($tokens as $token) {
                $query->where(function (Builder $inner) use ($token, $columns): void {
                    foreach ($columns as $column) {
                        $inner->orWhere($column, 'like', "%{$token}%");
                    }
                });
            }
        });
    }

    /**
     * @param  array<int, array{field: string, operator: string, value: mixed}>  $filters
     * @param  list<string>  $allowedFields
     */
    private function applyFilters(Builder $builder, array $filters, array $allowedFields): void
    {
        foreach ($filters as $filter) {
            $field = $filter['field'] ?? null;
            $operator = $filter['operator'] ?? '=';
            $value = $filter['value'] ?? null;
            if ($field === null) {
                continue;
            }
            if (! in_array($field, $allowedFields, true)) {
                continue;
            }

            $builder->where(function (Builder $query) use ($field, $operator, $value): void {
                $normalized = strtolower((string) $operator);

                match ($normalized) {
                    'contains' => $query->where($field, 'like', '%'.$value.'%'),
                    'not_contains' => $query->where($field, 'not like', '%'.$value.'%'),
                    'starts_with' => $query->where($field, 'like', $value.'%'),
                    'ends_with' => $query->where($field, 'like', '%'.$value),
                    'in' => $query->whereIn($field, (array) $value),
                    'not_in' => $query->whereNotIn($field, (array) $value),
                    'gte', '>=' => $query->where($field, '>=', $value),
                    'lte', '<=' => $query->where($field, '<=', $value),
                    '!=' => $query->where($field, '!=', $value),
                    default => $query->where($field, $value),
                };
            });
        }
    }

    /**
     * @return list<string>
     */
    private function tokenize(string $query): array
    {
        return collect(str_getcsv($query, ' ', escape: '\\'))
            ->filter()
            ->map(fn (string $token): string => trim($token))
            ->filter()
            ->values()
            ->all();
    }
}
