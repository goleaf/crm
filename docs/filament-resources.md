# Filament Resources Documentation

**Version:** Laravel 12.0 | Filament 4.0  
**Last Updated:** December 7, 2025

---

## Project Resource

### Overview
The Project Resource provides comprehensive project management capabilities including scheduling, budgeting, task management, and timeline visualization.

### Resource Structure

**Location:** `app/Filament/Resources/ProjectResource.php`

**Key Features:**
- Project CRUD operations
- Schedule visualization with Gantt chart export
- Budget tracking and variance analysis
- Critical path calculation
- Task dependency management
- Team member allocation

### Pages

#### ViewProjectSchedule

**Location:** `app/Filament/Resources/ProjectResource/Pages/ViewProjectSchedule.php`

**Purpose:** Displays comprehensive project scheduling information including critical path, timeline, and budget analysis.

**Key Components:**
- **Gantt Chart Export:** JSON export compatible with Gantt chart libraries
- **Budget Summary:** Real-time cost tracking with variance analysis
- **Critical Path:** Identifies tasks that determine project duration
- **Schedule Widget:** Interactive summary of project health

**Recent Changes (2025-12-07):**
- ✅ Fixed `$view` property to be instance-level (non-static) per Filament v4.3+ conventions
- Previously was `protected static string $view` which is deprecated in v4
- Now correctly uses `protected string $view` for dynamic view resolution

**Filament v4.3+ Compatibility:**
```php
// ✅ Correct (v4)
protected string $view = 'filament.resources.project-resource.pages.view-project-schedule';

// ❌ Deprecated (v3)
protected static string $view = 'filament.resources.project-resource.pages.view-project-schedule';
```

**Performance Optimizations:**
- Critical path calculations cached (15-minute TTL)
- Budget summaries cached (10-minute TTL)
- Database queries optimized with composite indexes
- N+1 query prevention through unified task loading

**Usage Example:**
```php
// Access the schedule page
$url = ProjectResource::getUrl('schedule', ['record' => $project]);

// In resource navigation
Action::make('view_schedule')
    ->url(fn (Project $record): string => 
        ProjectResource::getUrl('schedule', ['record' => $record])
    );
```

**View Data Structure:**
```php
[
    'project' => Project,           // The project record
    'ganttData' => [                // Gantt chart export data
        'id' => int,
        'name' => string,
        'start' => string,          // Y-m-d format
        'end' => string,
        'progress' => float,
        'tasks' => array,
        'milestones' => array,
        'critical_path' => array,
    ],
    'budgetSummary' => [            // Budget analysis
        'budget' => float|null,
        'actual_cost' => float,
        'variance' => float|null,
        'utilization_percentage' => float|null,
        'is_over_budget' => bool,
        'task_breakdown' => array,
        'total_billable_hours' => float,
    ],
]
```

**Widget Integration:**
```php
protected function getHeaderWidgets(): array
{
    return [
        ProjectScheduleWidget::make(['project' => $this->record]),
    ];
}
```

**Authorization:**
- Requires `view` permission on Project model
- Respects team boundaries (multi-tenancy)
- Automatically scoped to current tenant in Filament v4.3+

**Related Documentation:**
- [Performance Optimization Guide](./performance-project-schedule.md)
- [Project Scheduling Service](./api/project-scheduling-service.md)
- [Testing Guide](../tests/Feature/Filament/Resources/ProjectResource/Pages/ViewProjectScheduleTest.php)

---

## Translation Keys

All Project Resource UI elements use translation keys following the project's localization conventions:

**Navigation:**
- `app.navigation.workspace` - Navigation group

**Labels:**
- `app.labels.project_schedule` - Page title
- `app.labels.gantt_chart_data` - Gantt section heading
- `app.labels.budget_summary` - Budget section heading
- `app.labels.budget` - Budget field
- `app.labels.actual_cost` - Actual cost field
- `app.labels.variance` - Variance field
- `app.labels.utilization` - Utilization percentage

**Actions:**
- `app.actions.view_schedule` - View schedule button
- `app.actions.export_json` - Export JSON button

**Messages:**
- `app.messages.gantt_export_description` - Gantt export help text
- `app.messages.gantt_export_help` - Detailed export instructions

---

## Best Practices

### Filament v4.3+ Conventions

1. **Instance Properties:**
   - Use instance-level properties for page-specific configuration
   - Avoid static properties unless truly shared across all instances

2. **Schema System:**
   - Use `Filament\Schemas\Schema` for all form/infolist definitions
   - Mix form and infolist components when appropriate

3. **Performance:**
   - Implement caching for expensive calculations
   - Use database indexes for frequently queried relationships
   - Eager load relationships to prevent N+1 queries

4. **Translations:**
   - Never hardcode user-facing strings
   - Use `__()` helper with organized translation keys
   - Keep keys stable and descriptive

### Testing

All resource pages should have comprehensive test coverage:

```php
it('can render the page', function () {
    $project = Project::factory()->create(['team_id' => $this->team->id]);
    
    livewire(ViewProjectSchedule::class, ['record' => $project->id])
        ->assertSuccessful();
});

it('provides gantt data with project details', function () {
    $project = Project::factory()->create(['team_id' => $this->team->id]);
    
    $component = livewire(ViewProjectSchedule::class, ['record' => $project->id]);
    $ganttData = $component->get('ganttData');
    
    expect($ganttData)
        ->toHaveKey('id', $project->id)
        ->toHaveKey('tasks')
        ->toHaveKey('critical_path');
});
```

---

## Migration Notes

### From Filament v3 to v4

**Breaking Changes:**
- Static `$view` property should be instance-level
- Form/Infolist classes replaced with unified Schema
- Action imports consolidated to single namespace

**Migration Steps:**
1. Change `protected static string $view` to `protected string $view`
2. Update Schema imports from `Filament\Forms\Form` to `Filament\Schemas\Schema`
3. Update Action imports to `Filament\Actions\Action`
4. Test all page rendering and functionality

---

## Related Files

- `app/Filament/Resources/ProjectResource.php` - Main resource
- `app/Filament/Resources/ProjectResource/Pages/ViewProject.php` - View page
- `app/Filament/Resources/ProjectResource/Pages/EditProject.php` - Edit page
- `app/Filament/Resources/ProjectResource/Pages/ListProjects.php` - List page
- `app/Filament/Widgets/ProjectScheduleWidget.php` - Schedule widget
- `app/Services/ProjectSchedulingService.php` - Scheduling calculations
- `app/Models/Project.php` - Project model
- `resources/views/filament/resources/project-resource/pages/view-project-schedule.blade.php` - Blade view
- `tests/Feature/Filament/Resources/ProjectResource/Pages/ViewProjectScheduleTest.php` - Tests
