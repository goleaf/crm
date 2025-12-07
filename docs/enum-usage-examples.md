# Enum Usage Examples

## Quick Reference

### In Filament Resources

```php
use App\Enums\ProjectStatus;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

// Form Select
Select::make('status')
    ->label(__('app.labels.status'))
    ->options(ProjectStatus::toSelectArray())
    ->default(ProjectStatus::PLANNING->value)
    ->required();

// Table Column
TextColumn::make('status')
    ->label(__('app.labels.status'))
    ->badge()
    ->color(fn (ProjectStatus $state) => $state->getColor())
    ->icon(fn (ProjectStatus $state) => $state->getIcon());

// Table Filter
SelectFilter::make('status')
    ->options(ProjectStatus::toSelectArray())
    ->multiple();
```

### In Models

```php
use App\Enums\ProjectStatus;

class Project extends Model
{
    protected $casts = [
        'status' => ProjectStatus::class,
    ];

    // Usage
    public function activate(): void
    {
        if ($this->status->canTransitionTo(ProjectStatus::ACTIVE)) {
            $this->update(['status' => ProjectStatus::ACTIVE]);
        }
    }

    public function scopeActive($query)
    {
        return $query->where('status', ProjectStatus::ACTIVE);
    }
}
```

### In Validation

```php
use App\Enums\ProjectStatus;
use App\Rules\EnumValue;

// Using custom rule
$request->validate([
    'status' => ['required', new EnumValue(ProjectStatus::class)],
]);

// Using built-in helper
$request->validate([
    'status' => ['required', ProjectStatus::rule()],
]);

// Using Laravel's Rule
use Illuminate\Validation\Rule;

$request->validate([
    'status' => ['required', Rule::in(ProjectStatus::values())],
]);
```

### In Controllers

```php
use App\Enums\ProjectStatus;

class ProjectController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'status' => ['required', new EnumValue(ProjectStatus::class)],
        ]);

        $project = Project::create([
            'name' => $validated['name'],
            'status' => ProjectStatus::from($validated['status']),
        ]);

        return redirect()->route('projects.show', $project);
    }

    public function updateStatus(Project $project, string $status)
    {
        $newStatus = ProjectStatus::fromValueOrNull($status);

        if (!$newStatus) {
            abort(400, 'Invalid status');
        }

        if (!$project->status->canTransitionTo($newStatus)) {
            abort(422, 'Invalid status transition');
        }

        $project->update(['status' => $newStatus]);

        return back()->with('success', 'Status updated');
    }
}
```

### In API Resources

```php
use App\Enums\ProjectStatus;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->getLabel(),
                'color' => $this->status->getColor(),
                'icon' => $this->status->getIcon(),
            ],
            'can_modify' => $this->status->allowsModifications(),
            'allowed_transitions' => array_map(
                fn (ProjectStatus $status) => [
                    'value' => $status->value,
                    'label' => $status->getLabel(),
                ],
                $this->status->allowedTransitions()
            ),
        ];
    }
}
```

### In Livewire Components

```php
use App\Enums\ProjectStatus;
use Livewire\Component;

class ProjectStatusUpdater extends Component
{
    public Project $project;
    public string $newStatus;

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->newStatus = $project->status->value;
    }

    public function getAvailableStatusesProperty()
    {
        return ProjectStatus::collect()
            ->filter(fn (ProjectStatus $status) => 
                $this->project->status->canTransitionTo($status)
            )
            ->mapWithKeys(fn (ProjectStatus $status) => [
                $status->value => $status->getLabel(),
            ])
            ->toArray();
    }

    public function updateStatus()
    {
        $status = ProjectStatus::from($this->newStatus);

        if (!$this->project->status->canTransitionTo($status)) {
            $this->addError('newStatus', 'Invalid transition');
            return;
        }

        $this->project->update(['status' => $status]);
        
        $this->dispatch('status-updated');
    }

    public function render()
    {
        return view('livewire.project-status-updater');
    }
}
```

### In Blade Templates

```blade
{{-- Display status badge --}}
<span class="badge badge-{{ $project->status->getColor() }}">
    {{ $project->status->getLabel() }}
</span>

{{-- Status select dropdown --}}
<select name="status">
    @foreach(App\Enums\ProjectStatus::toSelectArray() as $value => $label)
        <option value="{{ $value }}" @selected($project->status->value === $value)>
            {{ $label }}
        </option>
    @endforeach
</select>

{{-- Conditional display --}}
@if($project->status->isActive())
    <div class="alert alert-info">
        This project is currently active.
    </div>
@endif

@if($project->status->allowsModifications())
    <button>Edit Project</button>
@endif
```

### In Database Seeders

```php
use App\Enums\ProjectStatus;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        // Create projects with different statuses
        foreach (ProjectStatus::cases() as $status) {
            Project::factory()
                ->count(5)
                ->create(['status' => $status]);
        }

        // Create random status projects
        Project::factory()
            ->count(10)
            ->create(['status' => ProjectStatus::random()]);
    }
}
```

### In Factories

```php
use App\Enums\ProjectStatus;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(),
            'status' => ProjectStatus::random(),
        ];
    }

    public function planning(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::PLANNING,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::ACTIVE,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::COMPLETED,
        ]);
    }
}
```

### In Tests

```php
use App\Enums\ProjectStatus;

it('can create project with enum status', function () {
    $project = Project::factory()->create([
        'status' => ProjectStatus::PLANNING,
    ]);

    expect($project->status)->toBe(ProjectStatus::PLANNING);
});

it('validates enum values', function () {
    $response = $this->postJson('/api/projects', [
        'name' => 'Test',
        'status' => 'invalid',
    ]);

    $response->assertJsonValidationErrors('status');
});

it('enforces status transitions', function () {
    $project = Project::factory()->create([
        'status' => ProjectStatus::PLANNING,
    ]);

    expect($project->status->canTransitionTo(ProjectStatus::ACTIVE))->toBeTrue();
    expect($project->status->canTransitionTo(ProjectStatus::COMPLETED))->toBeFalse();
});

it('can query by enum status', function () {
    Project::factory()->count(3)->create(['status' => ProjectStatus::ACTIVE]);
    Project::factory()->count(2)->create(['status' => ProjectStatus::PLANNING]);

    $activeProjects = Project::where('status', ProjectStatus::ACTIVE)->get();

    expect($activeProjects)->toHaveCount(3);
});
```

### In Policies

```php
use App\Enums\ProjectStatus;

class ProjectPolicy
{
    public function update(User $user, Project $project): bool
    {
        // Only allow updates if project is not in terminal state
        return $project->status->allowsModifications() 
            && $user->can('edit-projects');
    }

    public function delete(User $user, Project $project): bool
    {
        // Only allow deletion of planning projects
        return $project->status === ProjectStatus::PLANNING
            && $user->can('delete-projects');
    }

    public function complete(User $user, Project $project): bool
    {
        return $project->status->canTransitionTo(ProjectStatus::COMPLETED)
            && $user->can('complete-projects');
    }
}
```

### In Observers

```php
use App\Enums\ProjectStatus;

class ProjectObserver
{
    public function updating(Project $project): void
    {
        if ($project->isDirty('status')) {
            $oldStatus = ProjectStatus::from($project->getOriginal('status'));
            $newStatus = $project->status;

            if (!$oldStatus->canTransitionTo($newStatus)) {
                throw new \InvalidArgumentException(
                    "Cannot transition from {$oldStatus->value} to {$newStatus->value}"
                );
            }

            // Log the transition
            activity()
                ->performedOn($project)
                ->withProperties([
                    'old_status' => $oldStatus->value,
                    'new_status' => $newStatus->value,
                ])
                ->log('status_changed');
        }
    }

    public function updated(Project $project): void
    {
        if ($project->wasChanged('status')) {
            // Send notifications based on status
            match ($project->status) {
                ProjectStatus::ACTIVE => $project->notifyTeam('Project activated'),
                ProjectStatus::COMPLETED => $project->notifyTeam('Project completed'),
                default => null,
            };
        }
    }
}
```

### In Commands

```php
use App\Enums\ProjectStatus;
use Illuminate\Console\Command;

class ArchiveOldProjects extends Command
{
    protected $signature = 'projects:archive';

    public function handle(): void
    {
        $projects = Project::where('status', ProjectStatus::COMPLETED)
            ->where('completed_at', '<', now()->subMonths(6))
            ->get();

        $this->info("Found {$projects->count()} projects to archive");

        $projects->each(function (Project $project) {
            if ($project->status->canTransitionTo(ProjectStatus::CANCELLED)) {
                $project->update(['status' => ProjectStatus::CANCELLED]);
                $this->line("Archived: {$project->name}");
            }
        });

        $this->info('Done!');
    }
}
```

### In Notifications

```php
use App\Enums\ProjectStatus;
use Illuminate\Notifications\Notification;

class ProjectStatusChanged extends Notification
{
    public function __construct(
        public Project $project,
        public ProjectStatus $oldStatus,
        public ProjectStatus $newStatus
    ) {}

    public function toArray($notifiable): array
    {
        return [
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'old_status' => [
                'value' => $this->oldStatus->value,
                'label' => $this->oldStatus->getLabel(),
            ],
            'new_status' => [
                'value' => $this->newStatus->value,
                'label' => $this->newStatus->getLabel(),
                'color' => $this->newStatus->getColor(),
            ],
        ];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("Project Status Changed: {$this->project->name}")
            ->line("Status changed from {$this->oldStatus->getLabel()} to {$this->newStatus->getLabel()}")
            ->action('View Project', url("/projects/{$this->project->id}"));
    }
}
```

## Common Patterns

### Status Badge Component

```php
// Blade component
<x-status-badge :status="$project->status" />

// Component class
class StatusBadge extends Component
{
    public function __construct(
        public ProjectStatus $status
    ) {}

    public function render()
    {
        return view('components.status-badge');
    }
}

// Blade view
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $status->getColor() }}-100 text-{{ $status->getColor() }}-800">
    <x-icon :name="$status->getIcon()" class="w-4 h-4 mr-1" />
    {{ $status->getLabel() }}
</span>
```

### Enum Select Livewire Component

```php
class EnumSelect extends Component
{
    public string $enumClass;
    public $value;
    public string $label;

    public function mount(string $enumClass, $value = null, string $label = 'Select')
    {
        $this->enumClass = $enumClass;
        $this->value = $value;
        $this->label = $label;
    }

    public function getOptionsProperty()
    {
        return $this->enumClass::toSelectArray();
    }

    public function render()
    {
        return view('livewire.enum-select');
    }
}
```

## Tips & Tricks

1. **Use type hints**: Always type-hint enum parameters for better IDE support
2. **Leverage match expressions**: Use match() for cleaner conditional logic
3. **Cache options**: For large option lists, consider caching `toSelectArray()`
4. **Document transitions**: Clearly document allowed state transitions
5. **Test thoroughly**: Write tests for all enum methods and transitions
