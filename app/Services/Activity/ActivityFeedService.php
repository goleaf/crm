<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Models\Note;
use App\Models\Opportunity;
use App\Models\SupportCase;
use App\Models\Task;
use AustinW\UnionPaginator\UnionPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final readonly class ActivityFeedService
{
    public function __construct(
        private int $defaultPerPage = 25,
        private int $cacheTtl = 300
    ) {}

    /**
     * Get paginated team activity feed combining multiple models.
     */
    /**
     * Get paginated team activity feed combining multiple models.
     */
    public function getTeamActivity(int $teamId, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage ??= $this->defaultPerPage;

        return $this->getTeamActivityQuery($teamId)->latest()
            ->paginate($perPage);
    }

    /**
     * Get the query builder for union pagination to be used with Filament.
     */
    public function getTeamActivityQuery(int $teamId): Builder
    {
        // We use AustinW\UnionPaginator\UnionPaginator::make(...) usually, but that returns Paginator.
        // To get a Builder, we need to construct a Union query manually or use a helper if available.
        // The UnionPaginator::make($queries) takes an array of builders.
        // It doesn't seem to expose a "get Builder" method trivially without `paginate`.
        //
        // However, looking at the doc:
        // return DB::query()->fromSub($tasks->union($notes), 'activities');
        //
        // So I should construct the union query here.

        $tasks = $this->buildTasksQuery($teamId);
        $notes = $this->buildNotesQuery($teamId);
        $opportunities = $this->buildOpportunitiesQuery($teamId);
        $cases = $this->buildCasesQuery($teamId);

        return DB::query()
            ->fromSub(
                $tasks
                    ->union($notes)
                    ->union($opportunities)
                    ->union($cases),
                'activities'
            );
    }

    /**
     * Get cached team activity feed.
     */
    public function getCachedTeamActivity(int $teamId, int $page = 1, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage ??= $this->defaultPerPage;
        $cacheKey = "team.{$teamId}.activity.page.{$page}.per.{$perPage}";

        return Cache::remember($cacheKey, $this->cacheTtl, fn (): \Illuminate\Pagination\LengthAwarePaginator => $this->getTeamActivity($teamId, $perPage));
    }

    /**
     * Get paginated user activity feed.
     */
    public function getUserActivity(int $userId, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage ??= $this->defaultPerPage;

        $queries = [
            $this->buildUserTasksQuery($userId),
            $this->buildUserNotesQuery($userId),
            $this->buildUserOpportunitiesQuery($userId),
        ];

        return UnionPaginator::make($queries)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get recent activity for a specific record.
     */
    public function getRecordActivity(string $recordType, int $recordId, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage ??= $this->defaultPerPage;

        $queries = [
            $this->buildRecordTasksQuery($recordType, $recordId),
            $this->buildRecordNotesQuery($recordType, $recordId),
        ];

        return UnionPaginator::make($queries)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Clear cached activity for a team.
     */
    public function clearTeamActivityCache(int $teamId): void
    {
        $pattern = "team.{$teamId}.activity.*";
        Cache::forget($pattern);
    }

    /**
     * Build tasks query with consistent columns.
     */
    private function buildTasksQuery(int $teamId): Builder
    {
        return Task::query()
            ->select([
                'id',
                'title as name',
                'description',
                'created_at',
                'updated_at',
                'creator_id',
                DB::raw("'task' as activity_type"),
                DB::raw("'heroicon-o-check-circle' as icon"),
                DB::raw("'primary' as color"),
                DB::raw("CONCAT('/tasks/', id) as url"),
            ])
            ->where('team_id', $teamId)
            ->limit(100);
    }

    /**
     * Build notes query with consistent columns.
     */
    private function buildNotesQuery(int $teamId): Builder
    {
        return Note::query()
            ->select([
                'id',
                'title as name',
                'content as description',
                'created_at',
                'updated_at',
                'creator_id',
                DB::raw("'note' as activity_type"),
                DB::raw("'heroicon-o-document-text' as icon"),
                DB::raw("'info' as color"),
                DB::raw("CONCAT('/notes/', id) as url"),
            ])
            ->where('team_id', $teamId)
            ->limit(100);
    }

    /**
     * Build opportunities query with consistent columns.
     */
    private function buildOpportunitiesQuery(int $teamId): Builder
    {
        return Opportunity::query()
            ->select([
                'id',
                'title as name',
                'description',
                'created_at',
                'updated_at',
                'creator_id',
                DB::raw("'opportunity' as activity_type"),
                DB::raw("'heroicon-o-currency-dollar' as icon"),
                DB::raw("'success' as color"),
                DB::raw("CONCAT('/opportunities/', id) as url"),
            ])
            ->where('team_id', $teamId)
            ->limit(100);
    }

    /**
     * Build support cases query with consistent columns.
     */
    private function buildCasesQuery(int $teamId): Builder
    {
        return SupportCase::query()
            ->select([
                'id',
                'title as name',
                'description',
                'created_at',
                'updated_at',
                'creator_id',
                DB::raw("'case' as activity_type"),
                DB::raw("'heroicon-o-lifebuoy' as icon"),
                DB::raw("'warning' as color"),
                DB::raw("CONCAT('/cases/', id) as url"),
            ])
            ->where('team_id', $teamId)
            ->limit(100);
    }

    /**
     * Build user tasks query.
     */
    private function buildUserTasksQuery(int $userId): Builder
    {
        return Task::query()
            ->select([
                'id',
                'title as name',
                'description',
                'created_at',
                'updated_at',
                'creator_id',
                DB::raw("'task' as activity_type"),
                DB::raw("'heroicon-o-check-circle' as icon"),
                DB::raw("'primary' as color"),
                DB::raw("CONCAT('/tasks/', id) as url"),
            ])
            ->where('creator_id', $userId)
            ->limit(50);
    }

    /**
     * Build user notes query.
     */
    private function buildUserNotesQuery(int $userId): Builder
    {
        return Note::query()
            ->select([
                'id',
                'title as name',
                'content as description',
                'created_at',
                'updated_at',
                'creator_id',
                DB::raw("'note' as activity_type"),
                DB::raw("'heroicon-o-document-text' as icon"),
                DB::raw("'info' as color"),
                DB::raw("CONCAT('/notes/', id) as url"),
            ])
            ->where('creator_id', $userId)
            ->limit(50);
    }

    /**
     * Build user opportunities query.
     */
    private function buildUserOpportunitiesQuery(int $userId): Builder
    {
        return Opportunity::query()
            ->select([
                'id',
                'title as name',
                'description',
                'created_at',
                'updated_at',
                'creator_id',
                DB::raw("'opportunity' as activity_type"),
                DB::raw("'heroicon-o-currency-dollar' as icon"),
                DB::raw("'success' as color"),
                DB::raw("CONCAT('/opportunities/', id) as url"),
            ])
            ->where('creator_id', $userId)
            ->limit(50);
    }

    /**
     * Build record-specific tasks query.
     */
    private function buildRecordTasksQuery(string $recordType, int $recordId): Builder
    {
        $foreignKey = strtolower($recordType).'_id';

        return Task::query()
            ->select([
                'id',
                'title as name',
                'description',
                'created_at',
                'updated_at',
                'creator_id',
                DB::raw("'task' as activity_type"),
                DB::raw("'heroicon-o-check-circle' as icon"),
                DB::raw("'primary' as color"),
                DB::raw("CONCAT('/tasks/', id) as url"),
            ])
            ->where($foreignKey, $recordId);
    }

    /**
     * Build record-specific notes query.
     */
    private function buildRecordNotesQuery(string $recordType, int $recordId): Builder
    {
        return Note::query()
            ->select([
                'id',
                'title as name',
                'content as description',
                'created_at',
                'updated_at',
                'creator_id',
                DB::raw("'note' as activity_type"),
                DB::raw("'heroicon-o-document-text' as icon"),
                DB::raw("'info' as color"),
                DB::raw("CONCAT('/notes/', id) as url"),
            ])
            ->where('notable_type', $recordType)
            ->where('notable_id', $recordId);
    }
}
