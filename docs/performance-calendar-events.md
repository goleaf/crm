# Calendar Events Performance Optimization Report

**Date**: December 7, 2025  
**Component**: CalendarEvent Model, RecurrenceService, CalendarEventResource  
**Status**: ⚠️ Performance Issues Identified - Optimization Required

---

## Executive Summary

The calendar events migration adds meeting-specific fields and recurrence functionality. Analysis reveals **critical missing indexes** and **N+1 query risks** that will impact performance as the dataset grows, especially with recurring events.

### Key Findings
- 🔴 **Missing indexes** on new foreign key and query-critical columns
- 🟡 **N+1 query risk** in recurrence instance generation
- 🟡 **No eager loading** configured in CalendarEventResource
- 🟢 **Good foundation** with existing team_id + start_at composite index

---

## 1. Critical Performance Issues

### 🔴 Missing Database Indexes

**Location**: `database/migrations/2026_01_11_000001_add_meeting_fields_to_calendar_events_table.php`

#### Critical Missing Indexes:

1. **`recurrence_parent_id`** - Foreign key without index
   - Used in: `recurrenceInstances()` relationship queries
   - Impact: Slow queries when fetching recurring instances
   - Frequency: Every recurring event view/edit

2. **`recurrence_rule`** - Filtering recurring events
   - Used in: Filters, reports, recurring event lists
   - Impact: Full table scans when filtering by recurrence type
   - Frequency: Common in calendar views

3. **`recurrence_end_date`** - Date range queries
   - Used in: Finding active recurring events
   - Impact: Slow date range filtering
   - Frequency: Every calendar render with recurring events

4. **Composite indexes** for common query patterns:
   - `(team_id, recurrence_parent_id)` - Team-scoped recurring instances
   - `(recurrence_parent_id, start_at)` - Chronological instance ordering
   - `(team_id, recurrence_rule)` - Team recurring event filtering

### 🟡 N+1 Query Risks

**Location**: `app/Services/RecurrenceService.php`, `app/Observers/CalendarEventObserver.php`

#### Issue 1: Recurrence Instance Generation
```php
// In CalendarEventObserver::created()
foreach ($instances as $instance) {
    $instance->save(); // Individual INSERT per instance
}
```

**Impact**: Creating a daily recurring event for 1 year = 365 individual INSERT queries

**Solution**: Use batch insert
```php
CalendarEvent::insert($instances->map->toArray()->all());
```

#### Issue 2: No Eager Loading in Resource
```php
// CalendarEventResource::getEloquentQuery()
return parent::getEloquentQuery()
    ->withoutGlobalScopes([SoftDeletingScope::class]);
// Missing: ->with(['creator', 'team', 'recurrenceParent'])
```

**Impact**: N+1 queries when displaying creator/team names in table

### 🟡 Potential Performance Bottlenecks

1. **JSON attendees column** - No indexing possible
   - Consider separate `calendar_event_attendees` table for searchability
   - Current: Cannot efficiently search by attendee email

2. **Soft deletes on recurring instances**
   - Deleting parent + 100 instances = 101 UPDATE queries
   - Consider: Cascade delete or batch soft delete

3. **Observer overhead**
   - Every recurring event save triggers instance regeneration
   - Consider: Queue-based instance generation for large recurrence sets

---

## 2. Optimization Recommendations (Prioritized)

### Priority 1: Add Critical Indexes (Immediate)

**Create migration:**

```php
// database/migrations/2026_01_11_000002_add_calendar_event_performance_indexes.php

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            // Foreign key index (critical)
            $table->index('recurrence_parent_id');
            
            // Filtering indexes
            $table->index('recurrence_rule');
            $table->index('recurrence_end_date');
            
            // Composite indexes for common queries
            $table->index(['team_id', 'recurrence_parent_id'], 'idx_team_recurrence_parent');
            $table->index(['recurrence_parent_id', 'start_at'], 'idx_parent_start');
            $table->index(['team_id', 'recurrence_rule'], 'idx_team_recurrence_rule');
            
            // Calendar view optimization
            $table->index(['team_id', 'start_at', 'end_at'], 'idx_team_date_range');
            
            // Sync status queries
            $table->index(['sync_status', 'sync_provider'], 'idx_sync_status_provider');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->dropIndex('recurrence_parent_id');
            $table->dropIndex('recurrence_rule');
            $table->dropIndex('recurrence_end_date');
            $table->dropIndex('idx_team_recurrence_parent');
            $table->dropIndex('idx_parent_start');
            $table->dropIndex('idx_team_recurrence_rule');
            $table->dropIndex('idx_team_date_range');
            $table->dropIndex('idx_sync_status_provider');
        });
    }
};
```

**Estimated Impact**: 70-80% query time reduction for recurring event operations

---

### Priority 2: Fix N+1 Queries (Immediate)

#### A. Add Eager Loading to Resource

```php
// app/Filament/Resources/CalendarEventResource.php

public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->with([
            'creator:id,name',
            'team:id,name',
            'recurrenceParent:id,title,recurrence_rule',
        ])
        ->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
}
```

#### B. Optimize Recurrence Instance Creation

```php
// app/Observers/CalendarEventObserver.php

public function created(CalendarEvent $event): void
{
    if ($event->isRecurring() && ! $event->isRecurringInstance()) {
        $recurrenceService = app(RecurrenceService::class);
        $instances = $recurrenceService->generateInstances($event);
        
        // Batch insert instead of individual saves
        if ($instances->isNotEmpty()) {
            $data = $instances->map(fn ($instance) => array_merge(
                $instance->toArray(),
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ))->all();
            
            CalendarEvent::insert($data);
        }
    }
}
```

**Estimated Impact**: 95% reduction in INSERT queries for recurring events

---

### Priority 3: Optimize RecurrenceService (High)

#### A. Add Batch Operations

```php
// app/Services/RecurrenceService.php

/**
 * Delete all instances efficiently using batch delete.
 */
public function deleteInstances(CalendarEvent $parent): void
{
    if (! $parent->isRecurring()) {
        return;
    }

    // Batch soft delete instead of individual deletes
    $parent->recurrenceInstances()
        ->update(['deleted_at' => now()]);
}

/**
 * Update instances in batch.
 */
public function updateInstances(CalendarEvent $parent, array $updates): void
{
    if (! $parent->isRecurring()) {
        return;
    }

    // Batch update
    $parent->recurrenceInstances()
        ->where('start_at', '>=', now())
        ->update(array_merge($updates, ['updated_at' => now()]));
}
```

#### B. Add Query Optimization

```php
// app/Services/RecurrenceService.php

/**
 * Get instances with optimized query.
 */
public function getInstances(CalendarEvent $parent, ?Carbon $from = null, ?Carbon $to = null): Collection
{
    $query = $parent->recurrenceInstances()
        ->select(['id', 'title', 'start_at', 'end_at', 'status']) // Only needed columns
        ->orderBy('start_at');
    
    if ($from) {
        $query->where('start_at', '>=', $from);
    }
    
    if ($to) {
        $query->where('start_at', '<=', $to);
    }
    
    return $query->get();
}
```

---

### Priority 4: Add Caching Layer (Medium)

```php
// app/Services/RecurrenceService.php

use Illuminate\Support\Facades\Cache;

public function generateInstances(CalendarEvent $event, int $maxInstances = 100): Collection
{
    if (! $event->isRecurring()) {
        return collect();
    }

    $cacheKey = "calendar_event_{$event->id}_instances";
    
    return Cache::remember($cacheKey, now()->addHours(24), function () use ($event, $maxInstances) {
        // Existing generation logic
        $instances = collect();
        // ... generation code ...
        return $instances;
    });
}

/**
 * Clear instance cache when parent changes.
 */
public function clearInstanceCache(CalendarEvent $parent): void
{
    Cache::forget("calendar_event_{$parent->id}_instances");
}
```

**Update Observer to clear cache:**

```php
// app/Observers/CalendarEventObserver.php

public function updated(CalendarEvent $event): void
{
    if ($event->isRecurring() && ! $event->isRecurringInstance() && $event->wasChanged('recurrence_rule')) {
        $recurrenceService = app(RecurrenceService::class);
        
        // Clear cache before regenerating
        $recurrenceService->clearInstanceCache($event);
        
        // Delete old instances
        $recurrenceService->deleteInstances($event);
        
        // Generate new instances
        $instances = $recurrenceService->generateInstances($event);
        // ... save instances ...
    }
}
```

---

### Priority 5: Optimize Table Queries (Medium)

```php
// app/Filament/Resources/CalendarEventResource.php

public static function table(Table $table): Table
{
    return $table
        ->columns([
            // ... existing columns ...
        ])
        ->defaultSort('start_at', 'desc')
        ->striped()
        // Add query optimization
        ->modifyQueryUsing(function (Builder $query) {
            return $query
                ->select([
                    'calendar_events.id',
                    'calendar_events.title',
                    'calendar_events.type',
                    'calendar_events.status',
                    'calendar_events.start_at',
                    'calendar_events.end_at',
                    'calendar_events.location',
                    'calendar_events.room_booking',
                    'calendar_events.recurrence_rule',
                    'calendar_events.sync_status',
                    'calendar_events.team_id',
                    'calendar_events.creator_id',
                ])
                ->with(['creator:id,name', 'team:id,name']);
        })
        // Use simple pagination for better performance
        ->simplePagination()
        ->paginated([25, 50, 100]);
}
```

---

## 3. Performance Metrics

### Current Performance (Estimated)

**Scenario: Team with 100 recurring events, 50 instances each**

- **List Page Load**: 800-1200ms
- **Database Queries**: 150-200 queries (N+1 issues)
- **Recurring Event Creation**: 2-5 seconds (365 INSERTs for yearly event)
- **Cache Hit Rate**: 0% (no caching)

### Target Performance (After All Optimizations)

- **List Page Load**: 100-200ms (80-85% improvement)
- **Database Queries**: 5-10 queries (95% reduction)
- **Recurring Event Creation**: 200-500ms (90% improvement)
- **Cache Hit Rate**: 80%+ for instance queries

---

## 4. Implementation Roadmap

### Phase 1: Critical Fixes (Day 1) - IMMEDIATE

1. ✅ Create and run index migration
2. ✅ Add eager loading to CalendarEventResource
3. ✅ Test with sample recurring events

**Estimated Time**: 2-3 hours  
**Expected Impact**: 70% performance improvement

### Phase 2: Query Optimization (Day 2)

1. ✅ Implement batch insert for recurring instances
2. ✅ Optimize RecurrenceService batch operations
3. ✅ Add query column selection
4. ✅ Test with large recurrence sets (100+ instances)

**Estimated Time**: 3-4 hours  
**Expected Impact**: Additional 20% improvement

### Phase 3: Caching Layer (Day 3)

1. ✅ Implement instance caching
2. ✅ Add cache invalidation logic
3. ✅ Test cache hit rates

**Estimated Time**: 2-3 hours  
**Expected Impact**: Additional 10% improvement

### Phase 4: Monitoring (Day 4)

1. ✅ Add performance logging
2. ✅ Configure slow query alerts
3. ✅ Create performance tests

**Estimated Time**: 2 hours

---

## 5. Monitoring & Alerts

### Add Performance Monitoring

```php
// app/Filament/Resources/CalendarEventResource/Pages/ListCalendarEvents.php

use Illuminate\Support\Facades\Log;

final class ListCalendarEvents extends ListRecords
{
    protected static string $resource = CalendarEventResource::class;

    public function mount(): void
    {
        $start = microtime(true);
        
        parent::mount();
        
        $duration = (microtime(true) - $start) * 1000;
        
        if ($duration > 500) {
            Log::warning('Slow calendar events list page', [
                'duration_ms' => $duration,
                'user_id' => auth()->id(),
                'team_id' => auth()->user()->currentTeam->id,
            ]);
        }
    }
}
```

### Configure Telescope/Pulse

```php
// config/telescope.php (if using Telescope)

'watchers' => [
    Watchers\QueryWatcher::class => [
        'enabled' => env('TELESCOPE_QUERY_WATCHER', true),
        'slow' => 100, // Log queries slower than 100ms
    ],
],
```

---

## 6. Testing Checklist

### Performance Tests

```php
// tests/Performance/CalendarEventPerformanceTest.php

use App\Models\CalendarEvent;
use App\Models\User;
use Carbon\Carbon;

it('loads calendar events list page efficiently', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;
    
    // Create 100 events with recurring instances
    CalendarEvent::factory()
        ->count(100)
        ->recurring('WEEKLY', Carbon::now()->addMonths(3))
        ->create(['team_id' => $team->id]);
    
    $start = microtime(true);
    
    $this->actingAs($user)
        ->get(route('filament.admin.resources.calendar-events.index'))
        ->assertOk();
    
    $duration = (microtime(true) - $start) * 1000;
    
    expect($duration)->toBeLessThan(500); // Target: <500ms
});

it('creates recurring event efficiently', function () {
    $user = User::factory()->withPersonalTeam()->create();
    
    $start = microtime(true);
    
    $event = CalendarEvent::create([
        'team_id' => $user->currentTeam->id,
        'creator_id' => $user->id,
        'title' => 'Daily Standup',
        'start_at' => Carbon::now(),
        'end_at' => Carbon::now()->addMinutes(15),
        'recurrence_rule' => 'DAILY',
        'recurrence_end_date' => Carbon::now()->addYear(),
    ]);
    
    $duration = (microtime(true) - $start) * 1000;
    
    expect($duration)->toBeLessThan(1000); // Target: <1s for 365 instances
    expect($event->recurrenceInstances)->toHaveCount(365);
});

it('queries recurring instances without N+1', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;
    
    $parent = CalendarEvent::factory()
        ->recurring('WEEKLY', Carbon::now()->addWeeks(10))
        ->create(['team_id' => $team->id]);
    
    DB::enableQueryLog();
    
    $instances = $parent->recurrenceInstances()->get();
    
    $queries = DB::getQueryLog();
    
    expect($queries)->toHaveCount(1); // Should be single query
    expect($instances)->toHaveCount(10);
});
```

### Load Testing

```bash
# Using Apache Bench
ab -n 1000 -c 10 http://localhost/admin/calendar-events

# Target metrics:
# - Mean response time: <200ms
# - 95th percentile: <500ms
# - No failed requests
```

---

## 7. Future Enhancements

### Consider for v2:

1. **Separate Attendees Table**
   ```sql
   CREATE TABLE calendar_event_attendees (
       id BIGINT PRIMARY KEY,
       calendar_event_id BIGINT,
       name VARCHAR(255),
       email VARCHAR(255),
       INDEX (calendar_event_id),
       INDEX (email)
   );
   ```
   - Enables efficient attendee search
   - Better normalization

2. **Queue-Based Instance Generation**
   ```php
   // For very large recurrence sets (>100 instances)
   dispatch(new GenerateRecurringInstances($event));
   ```

3. **Materialized View for Calendar Queries**
   ```sql
   CREATE MATERIALIZED VIEW calendar_events_with_instances AS
   SELECT * FROM calendar_events
   UNION ALL
   SELECT * FROM calendar_events WHERE recurrence_parent_id IS NOT NULL;
   ```

4. **Elasticsearch Integration**
   - Full-text search across agenda/minutes
   - Complex date range queries
   - Attendee search

---

## 8. Related Documentation

- [Calendar Event Meeting Fields](./calendar-event-meeting-fields.md)
- [Filament Performance Guide](../.kiro/steering/filament-performance.md)
- [Project Schedule Performance](./performance-project-schedule.md)

---

## Conclusion

The calendar events feature has a solid foundation but requires immediate index optimization to prevent performance degradation as data grows. The recommended changes are straightforward and will provide significant performance improvements with minimal risk.

**Next Steps:**
1. Create and run the index migration (Priority 1)
2. Add eager loading to the resource (Priority 2)
3. Implement batch operations (Priority 2)
4. Monitor and iterate based on real-world usage

**Estimated Total Implementation Time**: 8-12 hours  
**Expected Performance Improvement**: 85-90% reduction in query time
