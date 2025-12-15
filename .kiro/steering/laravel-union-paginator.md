---
inclusion: always
---

# Laravel Union Paginator Integration

## Core Principles
- Use union pagination when combining data from multiple models into a single result set
- Always ensure consistent column counts and types across all union queries
- Leverage for activity feeds, unified search, timeline views, and audit logs
- Integrate seamlessly with Filament v4.3+ tables and widgets
- Follow service container pattern for union query builders

## When to Use Union Pagination

### ✅ Use For:
- Activity feeds combining tasks, notes, opportunities, cases
- Unified search across companies, people, opportunities
- Timeline views showing mixed record types
- Audit logs with different event types
- Dashboard widgets aggregating multiple data sources

### ❌ Don't Use For:
- Simple single-model queries (use standard pagination)
- Relationships that can be eager loaded (use `with()`)
- Data that should be normalized into a single table
- Real-time data requiring frequent updates

## Basic Pattern

```php
use AustinW\UnionPaginator\UnionPaginator;
use Illuminate\Support\Facades\DB;

// Build individual queries with consistent columns
$tasks = Task::query()
    ->select(['id', 'title as name', 'created_at', DB::raw("'task' as type")])
    ->where('team_id', $teamId);

$notes = Note::query()
    ->select(['id', 'title as name', 'created_at', DB::raw("'note' as type")])
    ->where('team_id', $teamId);

// Combine and paginate
$results = UnionPaginator::make([$tasks, $notes])
    ->orderBy('created_at', 'desc')
    ->paginate(25);
```

## Service Pattern

### Activity Feed Service
```php
namespace App\Services\Activity;

use AustinW\UnionPaginator\UnionPaginator;
use Illuminate\Pagination\LengthAwarePaginator;

class ActivityFeedService
{
    public function __construct(
        private readonly int $defaultPerPage = 25
    ) {}
    
    public function getTeamActivity(int $teamId, int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? $this->defaultPerPage;
        
        $tasks = $this->buildTasksQuery($teamId);
        $notes = $this->buildNotesQuery($teamId);
        $opportunities = $this->buildOpportunitiesQuery($teamId);
        
        return UnionPaginator::make([$tasks, $notes, $opportunities])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
    
    private function buildTasksQuery(int $teamId)
    {
        return Task::query()
            ->select([
                'id',
                'title as name',
                'description',
                'created_at',
                'creator_id',
                DB::raw("'task' as activity_type"),
                DB::raw("'heroicon-o-check-circle' as icon"),
                DB::raw("'primary' as color")
            ])
            ->where('team_id', $teamId);
    }
}
```

### Register in AppServiceProvider
```php
public function register(): void
{
    $this->app->singleton(ActivityFeedService::class, function ($app) {
        return new ActivityFeedService(
            defaultPerPage: config('app.pagination.default', 25)
        );
    });
}
```

## Filament Integration

### Custom Page with Union Table
```php
namespace App\Filament\Pages;

use App\Services\Activity\ActivityFeedService;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ActivityFeed extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static string $view = 'filament.pages.activity-feed';
    
    public function table(Table $table): Table
    {
        $service = app(ActivityFeedService::class);
        $teamId = filament()->getTenant()->id;
        
        return $table
            ->query($this->buildQuery($teamId))
            ->columns([
                TextColumn::make('name')->label(__('app.labels.name')),
                TextColumn::make('activity_type')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([15, 25, 50]);
    }
    
    protected function buildQuery(int $teamId)
    {
        // Build union query for Filament table
        $tasks = Task::query()
            ->select(['id', 'title as name', 'created_at', DB::raw("'task' as activity_type")])
            ->where('team_id', $teamId);
        
        $notes = Note::query()
            ->select(['id', 'title as name', 'created_at', DB::raw("'note' as activity_type")])
            ->where('team_id', $teamId);
        
        return DB::query()->fromSub($tasks->union($notes), 'activities');
    }
}
```

### Widget with Union Data
```php
namespace App\Filament\Widgets;

use Filament\Widgets\TableWidget;
use Filament\Tables\Table;

class RecentActivityWidget extends TableWidget
{
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        $teamId = filament()->getTenant()->id;
        
        return $table
            ->query($this->getUnionQuery($teamId))
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('activity_type')->badge(),
                TextColumn::make('created_at')->since(),
            ])
            ->paginated([10, 25]);
    }
}
```

## Column Consistency Rules

### ✅ Correct: Same Column Count and Types
```php
$tasks = Task::select([
    'id',                                    // int
    'title as name',                         // string
    'created_at',                            // datetime
    DB::raw("'task' as type")               // string
]);

$notes = Note::select([
    'id',                                    // int
    'title as name',                         // string
    'created_at',                            // datetime
    DB::raw("'note' as type")               // string
]);
```

### ❌ Wrong: Different Column Counts
```php
$tasks = Task::select(['id', 'title']);
$notes = Note::select(['id', 'title', 'content']); // ERROR: Different count
```

### ✅ Correct: Null Placeholders
```php
$tasks = Task::select([
    'id',
    'title as name',
    DB::raw('NULL as content'),  // Placeholder for missing column
    'created_at'
]);

$notes = Note::select([
    'id',
    'title as name',
    'content',                    // Actual column
    'created_at'
]);
```

## Performance Optimization

### Add Database Indexes
```sql
-- Index for union queries with team filtering and sorting
CREATE INDEX idx_tasks_team_created ON tasks(team_id, created_at DESC);
CREATE INDEX idx_notes_team_created ON notes(team_id, created_at DESC);
CREATE INDEX idx_opportunities_team_created ON opportunities(team_id, created_at DESC);
```

### Limit Individual Queries
```php
// Limit each query before union to improve performance
$tasks = Task::query()
    ->select([...])
    ->where('team_id', $teamId)
    ->limit(100);  // Limit before union

$notes = Note::query()
    ->select([...])
    ->where('team_id', $teamId)
    ->limit(100);  // Limit before union
```

### Cache Results
```php
use Illuminate\Support\Facades\Cache;

public function getCachedActivity(int $teamId, int $page = 1): LengthAwarePaginator
{
    $cacheKey = "team.{$teamId}.activity.page.{$page}";
    
    return Cache::remember($cacheKey, 300, function () use ($teamId) {
        return $this->getTeamActivity($teamId);
    });
}
```

## Testing Patterns

### Unit Test
```php
it('combines multiple models into paginated results', function () {
    $team = Team::factory()->create();
    
    Task::factory()->count(15)->create(['team_id' => $team->id]);
    Note::factory()->count(10)->create(['team_id' => $team->id]);
    
    $service = app(ActivityFeedService::class);
    $results = $service->getTeamActivity($team->id, perPage: 10);
    
    expect($results)->toHaveCount(10);
    expect($results->total())->toBe(25);
    expect($results->lastPage())->toBe(3);
});

it('respects team isolation in union queries', function () {
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    
    Task::factory()->count(5)->create(['team_id' => $team1->id]);
    Task::factory()->count(3)->create(['team_id' => $team2->id]);
    
    $service = app(ActivityFeedService::class);
    $results = $service->getTeamActivity($team1->id);
    
    expect($results->total())->toBe(5);
});
```

### Filament Test
```php
it('displays union results in filament table', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    
    $task = Task::factory()->create(['team_id' => $team->id]);
    $note = Note::factory()->create(['team_id' => $team->id]);
    
    $this->actingAs($user);
    
    livewire(ActivityFeed::class)
        ->assertCanSeeTableRecords([$task, $note]);
});
```

## Common Use Cases

### 1. Activity Feed
Combine tasks, notes, opportunities, cases into chronological feed

### 2. Unified Search
Search across companies, people, opportunities with single query

### 3. Timeline View
Show mixed events (calls, emails, meetings) in order

### 4. Audit Log
Combine create, update, delete events from multiple tables

### 5. Dashboard Widgets
Aggregate recent activity from various sources

## Best Practices

### DO:
- ✅ Use consistent column names across all queries
- ✅ Add type indicators (`DB::raw("'type' as record_type")`)
- ✅ Limit individual queries before union
- ✅ Add proper database indexes
- ✅ Cache expensive union queries
- ✅ Test pagination boundaries
- ✅ Respect tenant/team isolation
- ✅ Use services for complex union logic

### DON'T:
- ❌ Select different column counts in union queries
- ❌ Mix incompatible column types
- ❌ Forget to add ORDER BY
- ❌ Use `select('*')` in union queries
- ❌ Skip database indexes
- ❌ Ignore N+1 query problems
- ❌ Forget to test edge cases
- ❌ Hardcode union logic in controllers

## Troubleshooting

### Column Count Mismatch
**Error**: `The used SELECT statements have a different number of columns`

**Solution**: Add NULL placeholders for missing columns
```php
$tasks = Task::select(['id', 'title', DB::raw('NULL as content')]);
$notes = Note::select(['id', 'title', 'content']);
```

### Type Mismatch
**Error**: `Illegal mix of collations`

**Solution**: Cast columns to consistent types
```php
DB::raw('CAST(title AS CHAR) as name')
```

### Slow Performance
**Problem**: Union queries taking too long

**Solutions**:
1. Add indexes on filtered/sorted columns
2. Limit individual queries before union
3. Cache results with appropriate TTL
4. Use `simplePagination()` for large datasets

## Integration with Existing Patterns

Works seamlessly with:
- ✅ Laravel Container Services (service pattern)
- ✅ Filament v4.3+ Tables and Widgets
- ✅ Team/Tenant Scoping
- ✅ Translation System
- ✅ Testing Infrastructure (Pest)
- ✅ Queue-based Processing

## Related Documentation
- `docs/laravel-union-paginator.md` - Comprehensive guide
- `docs/laravel-container-services.md` - Service pattern
- `.kiro/steering/filament-conventions.md` - Filament integration
- `.kiro/steering/testing-standards.md` - Testing patterns
