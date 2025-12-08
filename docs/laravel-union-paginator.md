# Laravel Union Paginator Integration

## Overview

The Laravel Union Paginator package (`austinw/laravel-union-paginator`) enables pagination of SQL UNION queries in Laravel. This is particularly useful when you need to combine data from multiple models into a single paginated result set while maintaining consistent sorting and filtering.

## Installation

The package is already installed via Composer:

```bash
composer require austinw/laravel-union-paginator
```

## Core Concepts

### What is Union Pagination?

Union pagination allows you to:
- Combine results from multiple database tables/models
- Paginate the combined results efficiently
- Apply consistent sorting across different data sources
- Maintain type safety with custom DTOs
- Integrate seamlessly with Filament v4.3+ tables

### When to Use Union Pagination

✅ **Use when:**
- Combining activity feeds from multiple sources (tasks, notes, opportunities)
- Creating unified search results across different models
- Building timeline views with mixed record types
- Aggregating data from related but distinct tables

❌ **Don't use when:**
- Simple single-model queries (use standard pagination)
- Relationships can be eager loaded (use `with()`)
- Data can be normalized into a single table

## Basic Usage

### Simple Union Query

```php
use AustinW\UnionPaginator\UnionPaginator;
use Illuminate\Support\Facades\DB;

// Create base queries
$tasks = DB::table('tasks')
    ->select('id', 'title as name', 'created_at', DB::raw("'task' as type"))
    ->where('team_id', $teamId);

$notes = DB::table('notes')
    ->select('id', 'title as name', 'created_at', DB::raw("'note' as type"))
    ->where('team_id', $teamId);

// Combine and paginate
$results = UnionPaginator::make([$tasks, $notes])
    ->orderBy('created_at', 'desc')
    ->paginate(25);
```

### With Eloquent Models

```php
use App\Models\Task;
use App\Models\Note;
use App\Models\Opportunity;

// Build queries
$tasks = Task::query()
    ->select('id', 'title', 'created_at', DB::raw("'task' as type"))
    ->where('team_id', $teamId);

$notes = Note::query()
    ->select('id', 'title', 'created_at', DB::raw("'note' as type"))
    ->where('team_id', $teamId);

$opportunities = Opportunity::query()
    ->select('id', 'title', 'created_at', DB::raw("'opportunity' as type"))
    ->where('team_id', $teamId);

// Union and paginate
$results = UnionPaginator::make([$tasks, $notes, $opportunities])
    ->orderBy('created_at', 'desc')
    ->paginate(15);
```

## Advanced Patterns

### Activity Feed Example

```php
namespace App\Services\Activity;

use AustinW\UnionPaginator\UnionPaginator;
use App\Models\Task;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\SupportCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class ActivityFeedService
{
    public function __construct(
        private readonly int $defaultPerPage = 25
    ) {}
    
    public function getTeamActivity(int $teamId, int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? $this->defaultPerPage;
        
        // Build individual queries with consistent columns
        $tasks = Task::query()
            ->select([
                'id',
                'title as name',
                'description',
                'created_at',
                'updated_at',
                'creator_id',
                DB::raw("'task' as activity_type"),
                DB::raw("'heroicon-o-check-circle' as icon"),
                DB::raw("'primary' as color")
            ])
            ->where('team_id', $teamId);
        
        $notes = Note::query()
            ->select([
                'id',
                'title as name',
                'content as description',
                'created_at',
                'updated_at',
                'creator_id',
                DB::raw("'note' as activity_type"),
                DB::raw("'heroicon-o-document-text' as icon"),
                DB::raw("'info' as color")
            ])
            ->where('team_id', $teamId);
        
        $opportunities = Opportunity::query()
            ->select([
                'id',
                'title as name',
                'description',
                'created_at',
                'updated_at',
                'creator_id',
                DB::raw("'opportunity' as activity_type"),
                DB::raw("'heroicon-o-currency-dollar' as icon"),
                DB::raw("'success' as color")
            ])
            ->where('team_id', $teamId);
        
        $cases = SupportCase::query()
            ->select([
                'id',
                'title as name',
                'description',
                'created_at',
                'updated_at',
                'creator_id',
                DB::raw("'case' as activity_type"),
                DB::raw("'heroicon-o-lifebuoy' as icon"),
                DB::raw("'warning' as color")
            ])
            ->where('team_id', $teamId);
        
        // Combine and paginate
        return UnionPaginator::make([$tasks, $notes, $opportunities, $cases])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
    
    public function getUserActivity(int $userId, int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? $this->defaultPerPage;
        
        $tasks = Task::query()
            ->select([
                'id',
                'title as name',
                'created_at',
                DB::raw("'task' as activity_type")
            ])
            ->where('creator_id', $userId);
        
        $notes = Note::query()
            ->select([
                'id',
                'title as name',
                'created_at',
                DB::raw("'note' as activity_type")
            ])
            ->where('creator_id', $userId);
        
        return UnionPaginator::make([$tasks, $notes])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
```

### Search Across Multiple Models

```php
namespace App\Services\Search;

use AustinW\UnionPaginator\UnionPaginator;
use App\Models\Company;
use App\Models\People;
use App\Models\Opportunity;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class UnifiedSearchService
{
    public function search(string $query, int $teamId, int $perPage = 20): LengthAwarePaginator
    {
        $searchTerm = "%{$query}%";
        
        $companies = Company::query()
            ->select([
                'id',
                'name',
                'email',
                'phone',
                DB::raw("'company' as result_type"),
                DB::raw("CONCAT('/companies/', id) as url")
            ])
            ->where('team_id', $teamId)
            ->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('email', 'like', $searchTerm)
                  ->orWhere('phone', 'like', $searchTerm);
            });
        
        $people = People::query()
            ->select([
                'id',
                'name',
                'email',
                'phone',
                DB::raw("'person' as result_type"),
                DB::raw("CONCAT('/people/', id) as url")
            ])
            ->where('team_id', $teamId)
            ->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('email', 'like', $searchTerm)
                  ->orWhere('phone', 'like', $searchTerm);
            });
        
        $opportunities = Opportunity::query()
            ->select([
                'id',
                'title as name',
                DB::raw("NULL as email"),
                DB::raw("NULL as phone"),
                DB::raw("'opportunity' as result_type"),
                DB::raw("CONCAT('/opportunities/', id) as url")
            ])
            ->where('team_id', $teamId)
            ->where('title', 'like', $searchTerm);
        
        return UnionPaginator::make([$companies, $people, $opportunities])
            ->orderBy('name', 'asc')
            ->paginate($perPage);
    }
}
```

## Filament v4.3+ Integration

### Custom Table Widget

```php
namespace App\Filament\Widgets;

use AustinW\UnionPaginator\UnionPaginator;
use App\Models\Task;
use App\Models\Note;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RecentActivityWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\IconColumn::make('icon')
                    ->label('')
                    ->icon(fn ($record) => $record->icon),
                
                Tables\Columns\TextColumn::make('name')
                    ->label(__('app.labels.name'))
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('activity_type')
                    ->label(__('app.labels.type'))
                    ->badge()
                    ->color(fn ($record) => $record->color),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
    
    protected function getTableQuery(): Builder
    {
        $teamId = filament()->getTenant()->id;
        
        $tasks = Task::query()
            ->select([
                'id',
                'title as name',
                'created_at',
                DB::raw("'task' as activity_type"),
                DB::raw("'heroicon-o-check-circle' as icon"),
                DB::raw("'primary' as color")
            ])
            ->where('team_id', $teamId)
            ->limit(100);
        
        $notes = Note::query()
            ->select([
                'id',
                'title as name',
                'created_at',
                DB::raw("'note' as activity_type"),
                DB::raw("'heroicon-o-document-text' as icon"),
                DB::raw("'info' as color")
            ])
            ->where('team_id', $teamId)
            ->limit(100);
        
        // Return a builder that UnionPaginator can work with
        return DB::query()
            ->fromSub(
                $tasks->union($notes),
                'activities'
            );
    }
}
```

### Custom Filament Page with Union Pagination

```php
namespace App\Filament\Pages;

use AustinW\UnionPaginator\UnionPaginator;
use App\Models\Task;
use App\Models\Note;
use App\Models\Opportunity;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class ActivityFeed extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-rss';
    
    protected static string $view = 'filament.pages.activity-feed';
    
    public static function getNavigationLabel(): string
    {
        return __('app.navigation.activity_feed');
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\IconColumn::make('icon')
                    ->label('')
                    ->icon(fn ($record) => $record->icon)
                    ->color(fn ($record) => $record->color),
                
                Tables\Columns\TextColumn::make('name')
                    ->label(__('app.labels.name'))
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->name),
                
                Tables\Columns\TextColumn::make('activity_type')
                    ->label(__('app.labels.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => 
                        __("app.activity_types.{$state}")
                    ),
                
                Tables\Columns\TextColumn::make('description')
                    ->label(__('app.labels.description'))
                    ->limit(100)
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('app.labels.created_at'))
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('activity_type')
                    ->label(__('app.labels.type'))
                    ->options([
                        'task' => __('app.activity_types.task'),
                        'note' => __('app.activity_types.note'),
                        'opportunity' => __('app.activity_types.opportunity'),
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label(__('app.actions.view'))
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => $this->getRecordUrl($record)),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([15, 25, 50, 100]);
    }
    
    protected function getTableQuery()
    {
        $teamId = filament()->getTenant()->id;
        
        $tasks = Task::query()
            ->select([
                'id',
                'title as name',
                'description',
                'created_at',
                DB::raw("'task' as activity_type"),
                DB::raw("'heroicon-o-check-circle' as icon"),
                DB::raw("'primary' as color")
            ])
            ->where('team_id', $teamId);
        
        $notes = Note::query()
            ->select([
                'id',
                'title as name',
                'content as description',
                'created_at',
                DB::raw("'note' as activity_type"),
                DB::raw("'heroicon-o-document-text' as icon"),
                DB::raw("'info' as color")
            ])
            ->where('team_id', $teamId);
        
        $opportunities = Opportunity::query()
            ->select([
                'id',
                'title as name',
                'description',
                'created_at',
                DB::raw("'opportunity' as activity_type"),
                DB::raw("'heroicon-o-currency-dollar' as icon"),
                DB::raw("'success' as color")
            ])
            ->where('team_id', $teamId);
        
        return DB::query()
            ->fromSub(
                $tasks->union($notes)->union($opportunities),
                'activities'
            );
    }
    
    protected function getRecordUrl($record): string
    {
        return match ($record->activity_type) {
            'task' => route('filament.app.resources.tasks.view', ['record' => $record->id]),
            'note' => route('filament.app.resources.notes.view', ['record' => $record->id]),
            'opportunity' => route('filament.app.resources.opportunities.view', ['record' => $record->id]),
            default => '#',
        };
    }
}
```

## Performance Optimization

### Indexing Strategy

```sql
-- Add indexes for common union query columns
CREATE INDEX idx_tasks_team_created ON tasks(team_id, created_at DESC);
CREATE INDEX idx_notes_team_created ON notes(team_id, created_at DESC);
CREATE INDEX idx_opportunities_team_created ON opportunities(team_id, created_at DESC);

-- Add indexes for search columns
CREATE INDEX idx_tasks_title ON tasks(title);
CREATE INDEX idx_notes_title ON notes(title);
CREATE INDEX idx_opportunities_title ON opportunities(title);
```

### Query Optimization Tips

1. **Limit Individual Queries**: Add `->limit()` to each query before union
2. **Select Only Needed Columns**: Don't use `select('*')`
3. **Use Consistent Column Types**: Ensure union columns have matching types
4. **Add Proper Indexes**: Index columns used in WHERE and ORDER BY
5. **Cache Results**: Cache expensive union queries

```php
use Illuminate\Support\Facades\Cache;

public function getCachedActivity(int $teamId): LengthAwarePaginator
{
    $cacheKey = "team.{$teamId}.activity." . request('page', 1);
    
    return Cache::remember($cacheKey, 300, function () use ($teamId) {
        return $this->getTeamActivity($teamId);
    });
}
```

## Testing

### Unit Test Example

```php
use Tests\TestCase;
use App\Services\Activity\ActivityFeedService;
use App\Models\Task;
use App\Models\Note;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivityFeedServiceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_gets_team_activity_with_pagination(): void
    {
        $team = Team::factory()->create();
        
        Task::factory()->count(15)->create(['team_id' => $team->id]);
        Note::factory()->count(10)->create(['team_id' => $team->id]);
        
        $service = app(ActivityFeedService::class);
        $results = $service->getTeamActivity($team->id, perPage: 10);
        
        expect($results)->toHaveCount(10);
        expect($results->total())->toBe(25);
        expect($results->lastPage())->toBe(3);
    }
    
    public function test_activity_feed_respects_team_isolation(): void
    {
        $team1 = Team::factory()->create();
        $team2 = Team::factory()->create();
        
        Task::factory()->count(5)->create(['team_id' => $team1->id]);
        Task::factory()->count(3)->create(['team_id' => $team2->id]);
        
        $service = app(ActivityFeedService::class);
        $results = $service->getTeamActivity($team1->id);
        
        expect($results->total())->toBe(5);
    }
    
    public function test_activity_feed_orders_by_created_at_desc(): void
    {
        $team = Team::factory()->create();
        
        $oldTask = Task::factory()->create([
            'team_id' => $team->id,
            'created_at' => now()->subDays(5),
        ]);
        
        $newNote = Note::factory()->create([
            'team_id' => $team->id,
            'created_at' => now(),
        ]);
        
        $service = app(ActivityFeedService::class);
        $results = $service->getTeamActivity($team->id);
        
        expect($results->first()->id)->toBe($newNote->id);
        expect($results->first()->activity_type)->toBe('note');
    }
}
```

### Feature Test with Filament

```php
use Tests\TestCase;
use App\Filament\Pages\ActivityFeed;
use App\Models\User;
use App\Models\Team;
use App\Models\Task;
use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Livewire\livewire;

class ActivityFeedPageTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_render_activity_feed_page(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $user->teams()->attach($team);
        
        $this->actingAs($user);
        
        livewire(ActivityFeed::class)
            ->assertSuccessful();
    }
    
    public function test_can_see_activities_in_table(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $user->teams()->attach($team);
        
        $task = Task::factory()->create(['team_id' => $team->id]);
        $note = Note::factory()->create(['team_id' => $team->id]);
        
        $this->actingAs($user);
        
        livewire(ActivityFeed::class)
            ->assertCanSeeTableRecords([$task, $note]);
    }
    
    public function test_can_filter_activities_by_type(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $user->teams()->attach($team);
        
        Task::factory()->count(5)->create(['team_id' => $team->id]);
        Note::factory()->count(3)->create(['team_id' => $team->id]);
        
        $this->actingAs($user);
        
        livewire(ActivityFeed::class)
            ->filterTable('activity_type', 'task')
            ->assertCountTableRecords(5);
    }
}
```

## Best Practices

### DO:
- ✅ Use consistent column names across all queries
- ✅ Add type indicators (`DB::raw("'type' as record_type")`)
- ✅ Limit individual queries before union
- ✅ Add proper database indexes
- ✅ Cache expensive union queries
- ✅ Use DTOs for type safety
- ✅ Test pagination boundaries
- ✅ Respect tenant/team isolation

### DON'T:
- ❌ Select different column counts in union queries
- ❌ Mix incompatible column types
- ❌ Forget to add ORDER BY
- ❌ Use `select('*')` in union queries
- ❌ Skip database indexes
- ❌ Ignore N+1 query problems
- ❌ Forget to test edge cases

## Common Patterns

### Timeline View

```php
public function getTimeline(int $recordId, string $recordType): LengthAwarePaginator
{
    $tasks = Task::query()
        ->select(['id', 'title', 'created_at', DB::raw("'task' as type")])
        ->where("{$recordType}_id", $recordId);
    
    $notes = Note::query()
        ->select(['id', 'title', 'created_at', DB::raw("'note' as type")])
        ->where('notable_type', $recordType)
        ->where('notable_id', $recordId);
    
    return UnionPaginator::make([$tasks, $notes])
        ->orderBy('created_at', 'desc')
        ->paginate(20);
}
```

### Audit Log

```php
public function getAuditLog(int $teamId): LengthAwarePaginator
{
    $creates = DB::table('audits')
        ->select(['id', 'description', 'created_at', DB::raw("'create' as action")])
        ->where('team_id', $teamId)
        ->where('event', 'created');
    
    $updates = DB::table('audits')
        ->select(['id', 'description', 'created_at', DB::raw("'update' as action")])
        ->where('team_id', $teamId)
        ->where('event', 'updated');
    
    $deletes = DB::table('audits')
        ->select(['id', 'description', 'created_at', DB::raw("'delete' as action")])
        ->where('team_id', $teamId)
        ->where('event', 'deleted');
    
    return UnionPaginator::make([$creates, $updates, $deletes])
        ->orderBy('created_at', 'desc')
        ->paginate(50);
}
```

## Troubleshooting

### Column Count Mismatch

**Error**: `The used SELECT statements have a different number of columns`

**Solution**: Ensure all queries have the same number of columns:

```php
// ❌ BAD
$tasks = Task::select('id', 'title');
$notes = Note::select('id', 'title', 'content'); // Different column count

// ✅ GOOD
$tasks = Task::select('id', 'title', DB::raw('NULL as content'));
$notes = Note::select('id', 'title', 'content');
```

### Type Mismatch

**Error**: `Illegal mix of collations`

**Solution**: Cast columns to consistent types:

```php
$tasks = Task::select([
    'id',
    DB::raw('CAST(title AS CHAR) as name'),
    'created_at'
]);
```

### Performance Issues

**Problem**: Slow union queries

**Solutions**:
1. Add indexes on filtered/sorted columns
2. Limit individual queries before union
3. Cache results
4. Use `simplePagination()` for large datasets

## Related Documentation

- [Laravel Container Services](laravel-container-services.md) - Service pattern integration
- [Filament v4 Conventions](../.kiro/steering/filament-conventions.md) - Filament best practices
- [Testing Standards](../.kiro/steering/testing-standards.md) - Testing patterns
- [Performance Optimization](../.kiro/steering/filament-performance.md) - Performance tips

## External Resources

- [Package Repository](https://github.com/AustinW/laravel-union-paginator)
- [Laravel News Article](https://laravel-news.com/laravel-union-paginator)
- [Laravel Union Queries](https://laravel.com/docs/queries#unions)
- [SQL UNION Documentation](https://dev.mysql.com/doc/refman/8.0/en/union.html)
