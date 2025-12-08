<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\People;
use AustinW\UnionPaginator\UnionPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final readonly class UnifiedSearchService
{
    public function __construct(
        private int $defaultPerPage = 20
    ) {}

    /**
     * Search across multiple models with unified results.
     */
    public function search(string $query, int $teamId, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage ??= $this->defaultPerPage;
        $searchTerm = "%{$query}%";

        $queries = [
            $this->buildCompaniesQuery($searchTerm, $teamId),
            $this->buildPeopleQuery($searchTerm, $teamId),
            $this->buildOpportunitiesQuery($searchTerm, $teamId),
        ];

        return UnionPaginator::make($queries)
            ->orderBy('name', 'asc')
            ->paginate($perPage);
    }

    /**
     * Search with type filter.
     */
    public function searchByType(string $query, int $teamId, string $type, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage ??= $this->defaultPerPage;
        $searchTerm = "%{$query}%";

        $queryBuilder = match ($type) {
            'company' => $this->buildCompaniesQuery($searchTerm, $teamId),
            'person' => $this->buildPeopleQuery($searchTerm, $teamId),
            'opportunity' => $this->buildOpportunitiesQuery($searchTerm, $teamId),
            default => throw new \InvalidArgumentException("Invalid search type: {$type}"),
        };

        return UnionPaginator::make([$queryBuilder])
            ->orderBy('name', 'asc')
            ->paginate($perPage);
    }

    /**
     * Build companies search query.
     */
    private function buildCompaniesQuery(string $searchTerm, int $teamId): Builder
    {
        return Company::query()
            ->select([
                'id',
                'name',
                'email',
                'phone',
                'created_at',
                DB::raw("'company' as result_type"),
                DB::raw("'heroicon-o-building-office' as icon"),
                DB::raw("'primary' as color"),
                DB::raw("CONCAT('/companies/', id) as url"),
            ])
            ->where('team_id', $teamId)
            ->where(function (\Illuminate\Contracts\Database\Query\Builder $q) use ($searchTerm): void {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm)
                    ->orWhere('phone', 'like', $searchTerm);
            });
    }

    /**
     * Build people search query.
     */
    private function buildPeopleQuery(string $searchTerm, int $teamId): Builder
    {
        return People::query()
            ->select([
                'id',
                'name',
                'email',
                'phone',
                'created_at',
                DB::raw("'person' as result_type"),
                DB::raw("'heroicon-o-user' as icon"),
                DB::raw("'info' as color"),
                DB::raw("CONCAT('/people/', id) as url"),
            ])
            ->where('team_id', $teamId)
            ->where(function (\Illuminate\Contracts\Database\Query\Builder $q) use ($searchTerm): void {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm)
                    ->orWhere('phone', 'like', $searchTerm);
            });
    }

    /**
     * Build opportunities search query.
     */
    private function buildOpportunitiesQuery(string $searchTerm, int $teamId): Builder
    {
        return Opportunity::query()
            ->select([
                'id',
                'title as name',
                DB::raw('NULL as email'),
                DB::raw('NULL as phone'),
                'created_at',
                DB::raw("'opportunity' as result_type"),
                DB::raw("'heroicon-o-currency-dollar' as icon"),
                DB::raw("'success' as color"),
                DB::raw("CONCAT('/opportunities/', id) as url"),
            ])
            ->where('team_id', $teamId)
            ->where('title', 'like', $searchTerm);
    }
}
