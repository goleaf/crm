<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasNotesAndNotables;
use App\Models\Concerns\HasTaxonomies;
use App\Models\Concerns\HasTeam;
use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $team_id
 * @property int|null $creator_id
 * @property int|null $template_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property ProjectStatus $status
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property \Illuminate\Support\Carbon|null $actual_start_date
 * @property \Illuminate\Support\Carbon|null $actual_end_date
 * @property float|null $budget
 * @property float $actual_cost
 * @property string $currency
 * @property float $percent_complete
 * @property array|null $phases
 * @property array|null $milestones
 * @property array|null $deliverables
 * @property array|null $risks
 * @property array|null $issues
 * @property array|null $documentation
 * @property array|null $dashboard_config
 * @property bool $is_template
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class Project extends Model implements HasMedia
{
    use HasCreator;
    use HasFactory;
    use HasNotesAndNotables;
    use HasTaxonomies;
    use HasTeam;
    use HasUniqueSlug;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'team_id',
        'creator_id',
        'template_id',
        'name',
        'slug',
        'description',
        'status',
        'start_date',
        'end_date',
        'actual_start_date',
        'actual_end_date',
        'budget',
        'actual_cost',
        'currency',
        'percent_complete',
        'phases',
        'milestones',
        'deliverables',
        'risks',
        'issues',
        'documentation',
        'dashboard_config',
        'is_template',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'planning',
        'currency' => 'USD',
        'actual_cost' => 0,
        'percent_complete' => 0,
        'is_template' => false,
    ];

    /**
     * Initialize trait properties to keep PHP 8.4+ composition clean.
     */
    public function __construct(array $attributes = [])
    {
        $this->constraintFields = [];

        parent::__construct($attributes);
    }

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'actual_start_date' => 'date',
            'actual_end_date' => 'date',
            'budget' => 'decimal:2',
            'actual_cost' => 'decimal:2',
            'percent_complete' => 'decimal:2',
            'phases' => 'array',
            'milestones' => 'array',
            'deliverables' => 'array',
            'risks' => 'array',
            'issues' => 'array',
            'documentation' => 'array',
            'dashboard_config' => 'array',
            'is_template' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(static function (self $project): void {
            if ($project->team_id === null && auth()->check() && auth()->user()?->currentTeam !== null) {
                $project->team_id = auth()->user()->currentTeam->getKey();
            }
        });
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(self::class, 'template_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Project, $this>
     */
    public function projectsFromTemplate(): HasMany
    {
        return $this->hasMany(self::class, 'template_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')
            ->withPivot(['role', 'allocation_percentage'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Task, $this>
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'project_task')
            ->withTimestamps();
    }

    /**
     * Create a new project from this template.
     */
    public function createFromTemplate(string $name, array $overrides = []): self
    {
        if (! $this->is_template) {
            throw new \DomainException('Cannot create project from non-template.');
        }

        $project = self::create(array_merge([
            'team_id' => $this->team_id,
            'template_id' => $this->id,
            'name' => $name,
            'description' => $this->description,
            'status' => ProjectStatus::PLANNING,
            'budget' => $this->budget,
            'currency' => $this->currency,
            'phases' => $this->phases,
            'milestones' => $this->milestones,
            'deliverables' => $this->deliverables,
            'dashboard_config' => $this->dashboard_config,
            'is_template' => false,
        ], $overrides));

        // Copy team members
        foreach ($this->teamMembers as $member) {
            $project->teamMembers()->attach($member->id, [
                'role' => $member->pivot->role,
                'allocation_percentage' => $member->pivot->allocation_percentage,
            ]);
        }

        // Copy tasks (if any are associated with the template)
        foreach ($this->tasks as $templateTask) {
            $project->tasks()->attach($templateTask->id);
        }

        return $project;
    }

    /**
     * Calculate project completion percentage based on tasks.
     */
    public function calculatePercentComplete(): float
    {
        $tasks = $this->tasks()->get();

        if ($tasks->isEmpty()) {
            return 0;
        }

        $totalTasks = $tasks->count();
        $completedTasks = $tasks->filter(fn (Task $task): bool => $task->isCompleted())->count();

        return round(($completedTasks / $totalTasks) * 100, 2);
    }

    /**
     * Update the percent_complete field based on task completion.
     */
    public function updatePercentComplete(): void
    {
        $this->percent_complete = $this->calculatePercentComplete();
        $this->save();
    }

    /**
     * Calculate actual cost from all time entries across project tasks.
     */
    public function calculateActualCost(): float
    {
        return (float) $this->tasks()
            ->with('timeEntries')
            ->get()
            ->sum(fn (Task $task): float => $task->getTotalBillingAmount());
    }

    /**
     * Update the actual_cost field from time entries.
     */
    public function updateActualCost(): void
    {
        $this->actual_cost = $this->calculateActualCost();
        $this->save();
    }

    /**
     * Check if project is over budget.
     */
    public function isOverBudget(): bool
    {
        if ($this->budget === null) {
            return false;
        }

        return $this->actual_cost > $this->budget;
    }

    /**
     * Get budget variance (positive means under budget, negative means over budget).
     */
    public function budgetVariance(): ?float
    {
        if ($this->budget === null) {
            return null;
        }

        return $this->budget - $this->actual_cost;
    }

    /**
     * Get budget utilization percentage.
     */
    public function budgetUtilization(): ?float
    {
        if ($this->budget === null || $this->budget === 0) {
            return null;
        }

        return round(($this->actual_cost / $this->budget) * 100, 2);
    }

    /**
     * Get all time entries for this project across all tasks.
     *
     * @return \Illuminate\Support\Collection<int, TaskTimeEntry>
     */
    public function getAllTimeEntries(): \Illuminate\Support\Collection
    {
        return $this->tasks()
            ->with('timeEntries.user')
            ->get()
            ->flatMap(fn (Task $task): \Illuminate\Support\Collection => $task->timeEntries);
    }

    /**
     * Get budget summary with breakdown by task.
     *
     * @return array<string, mixed>
     */
    public function getBudgetSummary(): array
    {
        $tasks = $this->tasks()->with('timeEntries')->get();

        $taskBreakdown = $tasks->map(function (Task $task): array {
            $billableTime = $task->getTotalBillableTime();
            $billingAmount = $task->getTotalBillingAmount();

            return [
                'task_id' => $task->id,
                'task_name' => $task->title,
                'billable_minutes' => $billableTime,
                'billable_hours' => round($billableTime / 60, 2),
                'billing_amount' => $billingAmount,
            ];
        })->all();

        return [
            'project_id' => $this->id,
            'project_name' => $this->name,
            'currency' => $this->currency,
            'budget' => $this->budget,
            'actual_cost' => $this->actual_cost,
            'variance' => $this->budgetVariance(),
            'utilization_percentage' => $this->budgetUtilization(),
            'is_over_budget' => $this->isOverBudget(),
            'task_breakdown' => $taskBreakdown,
            'total_billable_minutes' => array_sum(array_column($taskBreakdown, 'billable_minutes')),
            'total_billable_hours' => round(array_sum(array_column($taskBreakdown, 'billable_minutes')) / 60, 2),
        ];
    }

    /**
     * Export time logs for reporting.
     *
     * @return array<int, array<string, mixed>>
     */
    public function exportTimeLogs(): array
    {
        return $this->getAllTimeEntries()
            ->map(fn (TaskTimeEntry $entry): array => [
                'task_id' => $entry->task_id,
                'task_name' => $entry->task->title,
                'user_id' => $entry->user_id,
                'user_name' => $entry->user->name,
                'started_at' => $entry->started_at?->toIso8601String(),
                'ended_at' => $entry->ended_at?->toIso8601String(),
                'duration_minutes' => $entry->duration_minutes,
                'duration_hours' => round($entry->duration_minutes / 60, 2),
                'is_billable' => $entry->is_billable,
                'billing_rate' => $entry->billing_rate,
                'billing_amount' => $entry->is_billable && $entry->billing_rate
                    ? round(($entry->duration_minutes / 60) * $entry->billing_rate, 2)
                    : 0,
                'note' => $entry->note,
            ])
            ->all();
    }

    /**
     * Export project data for Gantt chart visualization.
     *
     * @return array<string, mixed>
     */
    public function exportForGantt(): array
    {
        $schedulingService = resolve(\App\Services\ProjectSchedulingService::class);
        $timeline = $schedulingService->generateTimeline($this);
        $criticalPath = $schedulingService->calculateCriticalPath($this);
        $criticalTaskIds = $criticalPath->pluck('id')->toArray();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'start' => $timeline['start_date'],
            'end' => $timeline['end_date'],
            'progress' => $this->percent_complete,
            'tasks' => collect($timeline['tasks'])->map(fn (array $task): array => [
                'id' => $task['task_id'],
                'name' => $task['task_name'],
                'start' => $task['scheduled_start'],
                'end' => $task['scheduled_end'],
                'progress' => $task['percent_complete'],
                'is_critical' => in_array($task['task_id'], $criticalTaskIds, true),
                'is_milestone' => $task['is_milestone'],
                'dependencies' => $task['dependencies'],
                'assignees' => $task['assignees'],
            ])->all(),
            'milestones' => $timeline['milestones'],
            'critical_path' => $criticalTaskIds,
        ];
    }

    /**
     * Get the critical path for this project.
     *
     * @return \Illuminate\Support\Collection<int, Task>
     */
    public function getCriticalPath(): \Illuminate\Support\Collection
    {
        $schedulingService = resolve(\App\Services\ProjectSchedulingService::class);

        return $schedulingService->calculateCriticalPath($this);
    }

    /**
     * Get project timeline with scheduled tasks.
     *
     * @return array<string, mixed>
     */
    public function getTimeline(): array
    {
        $schedulingService = resolve(\App\Services\ProjectSchedulingService::class);

        return $schedulingService->generateTimeline($this);
    }

    /**
     * Get schedule summary with key metrics.
     *
     * @return array<string, mixed>
     */
    public function getScheduleSummary(): array
    {
        $schedulingService = resolve(\App\Services\ProjectSchedulingService::class);

        return $schedulingService->getScheduleSummary($this);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')
            ->useDisk(config('filesystems.default', 'public'));

        $this->addMediaCollection('attachments')
            ->useDisk(config('filesystems.default', 'public'));
    }
}
