# Test Generators

This directory contains generator classes for creating test data with realistic and varied properties for property-based testing.

## CalendarEventGenerator

The `CalendarEventGenerator` provides methods for creating `CalendarEvent` instances with realistic data for testing various scenarios.

### Basic Usage

```php
use Tests\Support\Generators\CalendarEventGenerator;

// Generate a basic calendar event
$event = CalendarEventGenerator::generate($team, $user);

// Generate with custom overrides
$event = CalendarEventGenerator::generate($team, $user, [
    'title' => 'Important Meeting',
    'type' => CalendarEventType::DEMO,
    'status' => CalendarEventStatus::CONFIRMED,
]);
```

### Specialized Generation Methods

#### Recurring Events
```php
// Generate a recurring event
$recurringEvent = CalendarEventGenerator::generateRecurring($team, $user);

// Generate a recurring instance
$instance = CalendarEventGenerator::generateRecurringInstance(
    $recurringEvent, 
    $recurringEvent->start_at->copy()->addWeek()
);
```

#### Events with External Sync
```php
// Generate event with sync data (Google, Outlook, Apple)
$syncedEvent = CalendarEventGenerator::generateWithSync($team, $user);
```

#### All-Day Events
```php
// Generate all-day event
$allDayEvent = CalendarEventGenerator::generateAllDay($team, $user);
```

#### Events with Specific Duration
```php
// Generate 90-minute meeting
$longMeeting = CalendarEventGenerator::generateWithDuration($team, 90, $user);
```

#### Events with Related Records
```php
// Link event to a company
$company = Company::factory()->create();
$event = CalendarEventGenerator::generateWithRelated($team, $company, $user);
```

### Bulk Generation

```php
// Generate multiple events
$events = CalendarEventGenerator::generateMultiple($team, 10, $user);

// Generate multiple events with base overrides
$meetings = CalendarEventGenerator::generateMultiple($team, 5, $user, [
    'type' => CalendarEventType::MEETING,
]);
```

### Edge Case Testing

```php
// Generate event with edge case data (long titles, empty attendees, etc.)
$edgeCaseEvent = CalendarEventGenerator::generateEdgeCase($team, $user);
```

### Data-Only Generation

```php
// Generate data array without persisting to database
$eventData = CalendarEventGenerator::generateData($team, $user);
```

## Features

### Realistic Data Generation
- **Dates**: Events are generated within realistic timeframes (-1 month to +3 months)
- **Durations**: Reasonable meeting durations (15 minutes to 4 hours)
- **Attendees**: 1-8 attendees with realistic names and emails
- **Locations**: Mix of physical locations and virtual meeting URLs
- **Content**: Realistic titles, agendas, and notes

### Validation and Error Handling
- Validates date relationships (start_at before end_at)
- Validates related record requirements
- Validates parent event requirements for recurring instances
- Throws descriptive exceptions for invalid inputs

### Performance Optimized
- Reuses base data generation logic to avoid duplication
- Efficient bulk generation methods
- Minimal database queries per event
- Memory-efficient for large datasets

## Testing Coverage

The generator is thoroughly tested with:

### Unit Tests (`CalendarEventGeneratorTest`)
- Basic generation functionality
- Override application
- Validation logic
- Edge case handling
- Error conditions

### Integration Tests (`CalendarEventGeneratorIntegrationTest`)
- Database relationships
- Model observers
- Query scopes
- Soft deletes
- Team isolation

### Property Tests (`CalendarEventGeneratorPropertyTest`)
- Date relationship invariants
- Team/creator ownership
- Enum value validity
- Recurring event consistency
- Attendee structure integrity

### Performance Tests (`CalendarEventGeneratorPerformanceTest`)
- Single event generation speed
- Bulk generation scalability
- Memory usage optimization
- Database query efficiency
- Concurrent generation simulation

## Best Practices

### Use Appropriate Methods
- Use `generate()` for basic events
- Use `generateRecurring()` for parent recurring events
- Use `generateRecurringInstance()` for recurring instances
- Use `generateMultiple()` for bulk operations
- Use `generateData()` when you don't need persistence

### Override Strategically
```php
// Good: Override specific fields you need to test
$event = CalendarEventGenerator::generate($team, $user, [
    'status' => CalendarEventStatus::CANCELLED,
]);

// Avoid: Overriding too many fields (defeats the purpose of random generation)
$event = CalendarEventGenerator::generate($team, $user, [
    'title' => 'Title',
    'type' => CalendarEventType::MEETING,
    'status' => CalendarEventStatus::SCHEDULED,
    'start_at' => now(),
    'end_at' => now()->addHour(),
    // ... too many overrides
]);
```

### Performance Considerations
```php
// Good: Use bulk generation for multiple events
$events = CalendarEventGenerator::generateMultiple($team, 50, $user);

// Avoid: Individual generation in loops
for ($i = 0; $i < 50; $i++) {
    $events[] = CalendarEventGenerator::generate($team, $user);
}
```

### Testing Patterns
```php
// Property-based testing
$this->forAll($this->teamGenerator(), $this->userGenerator())
    ->then(function (Team $team, User $user): void {
        $event = CalendarEventGenerator::generate($team, $user);
        
        // Test invariants
        $this->assertTrue($event->start_at->isBefore($event->end_at));
        $this->assertEquals($team->id, $event->team_id);
    });

// Edge case testing
$edgeEvent = CalendarEventGenerator::generateEdgeCase($team, $user);
// Test that your code handles edge cases gracefully

// Integration testing
$company = Company::factory()->create();
$event = CalendarEventGenerator::generateWithRelated($team, $company, $user);
$this->assertEquals($company->id, $event->related->id);
```

## Error Handling

The generator includes comprehensive error handling:

```php
// InvalidArgumentException for invalid date ranges
CalendarEventGenerator::generate($team, $user, [
    'start_at' => now()->addHour(),
    'end_at' => now(), // Error: start_at after end_at
]);

// InvalidArgumentException for invalid related records
$invalidRecord = new stdClass();
CalendarEventGenerator::generateWithRelated($team, $invalidRecord, $user);

// InvalidArgumentException for non-recurring parent events
$regularEvent = CalendarEventGenerator::generate($team, $user);
CalendarEventGenerator::generateRecurringInstance($regularEvent, now());
```

## Contributing

When extending the generator:

1. **Add comprehensive tests** for new methods
2. **Maintain performance** - test with bulk generation
3. **Validate inputs** - throw descriptive exceptions
4. **Document usage** - update this README
5. **Follow patterns** - use existing method signatures and conventions
6. **Test edge cases** - ensure robustness with unusual inputs

## Related Files

- `app/Models/CalendarEvent.php` - The model being generated
- `database/factories/CalendarEventFactory.php` - Laravel factory (used internally)
- `tests/Unit/Support/Generators/CalendarEventGeneratorTest.php` - Unit tests
- `tests/Feature/Support/Generators/CalendarEventGeneratorIntegrationTest.php` - Integration tests
- `tests/Unit/Properties/Support/CalendarEventGeneratorPropertyTest.php` - Property tests
- `tests/Performance/CalendarEventGeneratorPerformanceTest.php` - Performance tests