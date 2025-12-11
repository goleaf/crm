<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreationSource;
use App\Enums\CustomFields\TaskField;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasNotesAndNotables;
use App\Models\Concerns\HasTaxonomies;
use App\Models\Concerns\HasTeam;
use App\Observers\TaskObserver;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\TenantContextService;
use Spatie\EloquentSortable\SortableTrait;

/**
 * @property int            $id
 * @property Carbon|null    $deleted_at
 * @property CreationSource $creation_source
 * @property string         $createdBy
 *
 * @method void saveCustomFieldValue(CustomField $field, mixed $value, ?Model $tenant = null)
 */
#[ObservedBy(TaskObserver::class)]
final class Task extends Model implements HasCustomFields
{
    use HasCreator;

    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    use HasNotesAndNotables;
    use HasTaxonomies;
    use HasTeam;
    use SoftDeletes;
    use SortableTrait;
    use UsesCustomFields {
        saveCustomFieldValue as baseSaveCustomFieldValue;
    }

    /**
     * Cache resolved custom fields by tenant.
     *
     * @var array<string, CustomField|null>
     */
    private static array $customFieldCache = [];

    protected $fillable = [
        'title',
        'creation_source',
        'parent_id',
        'template_id',
        'start_date',
        'end_date',
        'estimated_duration_minutes',
        'percent_complete',
        'is_milestone',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'creation_source' => CreationSource::WEB,
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'creation_source' => CreationSource::class,
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'percent_complete' => 'decimal:2',
            'is_milestone' => 'boolean',
        ];
    }

    /**
     * @var array{order_column_name: 'order_column', sort_when_creating: true}
     */
    public array $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
    ];

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return BelongsTo<TaskTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(TaskTemplate::class, 'template_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Task, $this>
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Task, $this, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'task_dependencies',
            'task_id',
            'depends_on_task_id',
        )->withTimestamps();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Task, $this, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'task_dependencies',
            'depends_on_task_id',
            'task_id',
        )->withTimestamps();
    }

    /**
     * Legacy task categories (to be migrated off TaskCategory).
     *
     * @return BelongsToMany<TaskCategory, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(TaskCategory::class, 'task_task_category')
            ->withTimestamps();
    }

    /**
     * Taxonomy-powered task categories.
     *
     * @return MorphToMany<Taxonomy, $this>
     */
    public function taskTaxonomies(): MorphToMany
    {
        return $this->taxonomies()
            ->where('type', 'task_category')
            ->withPivot('created_at', 'updated_at');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\TaskChecklistItem, $this>
     */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(TaskChecklistItem::class)->orderBy('position');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\TaskComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\TaskTimeEntry, $this>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TaskTimeEntry::class)->latest();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\TaskReminder, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(TaskReminder::class)->latest('remind_at');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<\App\Models\TaskRecurrence, $this>
     */
    public function recurrence(): HasOne
    {
        return $this->hasOne(TaskRecurrence::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\TaskDelegation, $this>
     */
    public function delegations(): HasMany
    {
        return $this->hasMany(TaskDelegation::class)->latest();
    }

    /**
     * @return MorphToMany<Company, $this>
     */
    public function companies(): MorphToMany
    {
        return $this->morphedByMany(Company::class, 'taskable');
    }

    /**
     * @return MorphToMany<Opportunity, $this>
     */
    public function opportunities(): MorphToMany
    {
        return $this->morphedByMany(Opportunity::class, 'taskable');
    }

    /**
     * @return MorphToMany<People, $this>
     */
    public function people(): MorphToMany
    {
        return $this->morphedByMany(People::class, 'taskable');
    }

    /**
     * @return MorphToMany<SupportCase, $this>
     */
    public function cases(): MorphToMany
    {
        return $this->morphedByMany(SupportCase::class, 'taskable');
    }

    /**
     * @return MorphToMany<Lead, $this>
     */
    public function leads(): MorphToMany
    {
        return $this->morphedByMany(Lead::class, 'taskable');
    }

    /**
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_task')
            ->withTimestamps();
    }

    /**
     * Determine whether this task is blocked by open dependencies.
     */
    public function isBlocked(): bool
    {
        return $this->dependencies()
            ->with('customFieldValues.customField.options')
            ->get()
            ->contains(fn (Task $task): bool => ! $task->isCompleted());
    }

    /**
     * Determine whether this task is completed based on its status field.
     */
    public function isCompleted(): bool
    {
        return $this->statusLabel() === 'Completed';
    }

    /**
     * Calculate percent complete based on subtasks.
     * If no subtasks exist, returns the task's own percent_complete value.
     * Handles edge cases like null values and ensures proper rounding.
     */
    public function calculatePercentComplete(): float
    {
        // If task is completed, always return 100
        if ($this->isCompleted()) {
            return 100.0;
        }

        $subtasks = $this->subtasks()->get();

        if ($subtasks->isEmpty()) {
            // Return own percent_complete, defaulting to 0 if null
            return (float) ($this->percent_complete ?? 0);
        }

        $totalSubtasks = $subtasks->count();

        // Handle edge case of no subtasks (shouldn't happen but be safe)
        if ($totalSubtasks === 0) {
            return (float) ($this->percent_complete ?? 0);
        }

        $totalProgress = $subtasks->sum(fn (Task $task): float => $task->calculatePercentComplete());

        // Ensure result is between 0 and 100
        $calculated = round($totalProgress / $totalSubtasks, 2);

        return min(100.0, max(0.0, $calculated));
    }

    /**
     * Update the percent_complete field based on subtasks or completion status.
     * Automatically propagates changes to parent task.
     */
    public function updatePercentComplete(): void
    {
        $newPercentComplete = $this->calculatePercentComplete();

        // Only update if value has changed to avoid unnecessary saves
        if ($this->percent_complete !== $newPercentComplete) {
            $this->percent_complete = $newPercentComplete;
            $this->save();
        }

        // Update parent task if exists
        if ($this->parent_id !== null && $this->parent !== null) {
            $this->parent->updatePercentComplete();
        }
    }

    /**
     * Get the earliest start date based on dependencies.
     * Returns the maximum of all dependency end dates, or the task's start date if later.
     */
    public function getEarliestStartDate(): ?\Illuminate\Support\Carbon
    {
        $dependencies = $this->dependencies()->get();

        if ($dependencies->isEmpty()) {
            return $this->start_date;
        }

        $latestDependencyEnd = $dependencies
            ->map(fn (Task $task): ?\Illuminate\Support\Carbon => $task->end_date)
            ->filter()
            ->max();

        if ($latestDependencyEnd === null) {
            return $this->start_date;
        }

        // If task has no start date, the earliest it can start is after dependencies
        if ($this->start_date === null) {
            return $latestDependencyEnd;
        }

        // Return the later of the two dates (task can't start before dependencies finish)
        return $this->start_date->greaterThan($latestDependencyEnd)
            ? $this->start_date
            : $latestDependencyEnd;
    }

    /**
     * Check if task dates violate dependency constraints.
     */
    public function violatesDependencyConstraints(): bool
    {
        if ($this->start_date === null) {
            return false;
        }

        $dependencies = $this->dependencies()->get();

        foreach ($dependencies as $dependency) {
            if ($dependency->end_date !== null && $this->start_date->lessThan($dependency->end_date)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get total billable time logged for this task.
     */
    public function getTotalBillableTime(): int
    {
        return $this->timeEntries()
            ->where('is_billable', true)
            ->sum('duration_minutes');
    }

    /**
     * Get total billing amount for this task.
     */
    public function getTotalBillingAmount(): float
    {
        return (float) $this->timeEntries()
            ->where('is_billable', true)
            ->whereNotNull('billing_rate')
            ->get()
            ->sum(fn (TaskTimeEntry $entry): float => ($entry->duration_minutes / 60) * $entry->billing_rate);
    }

    /**
     * Human-friendly status label.
     */
    public function statusLabel(): ?string
    {
        return $this->optionLabelForField(TaskField::STATUS->value);
    }

    /**
     * Human-friendly priority label.
     */
    public function priorityLabel(): ?string
    {
        return $this->optionLabelForField(TaskField::PRIORITY->value);
    }

    /**
     * Parsed due date for filtering and reminders.
     */
    public function dueDate(): ?Carbon
    {
        $field = $this->resolveCustomField(TaskField::DUE_DATE->value);

        if (! $field instanceof CustomField) {
            return null;
        }

        $value = $this->getCustomFieldValue($field);

        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return \Illuminate\Support\Facades\Date::instance($value);
        }

        if (is_string($value)) {
            try {
                return \Illuminate\Support\Facades\Date::parse($value);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    /**
     * Apply common task filters for list views.
     *
     * @param array{assignees?: array<int>, categories?: array<int>, status?: array<int|string>, priority?: array<int|string>, blocked?: bool} $filters
     *
     * @return \Illuminate\Database\Eloquent\Builder<Task>
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function applyTaskFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['assignees'])) {
            $query->whereHas('assignees', fn (Builder $builder): mixed => $builder->whereIn('users.id', $filters['assignees']));
        }

        if (! empty($filters['categories'])) {
            $query->whereHas('categories', fn (Builder $builder): mixed => $builder->whereIn('task_categories.id', $filters['categories']));
        }

        if (! empty($filters['status'])) {
            $this->applyCustomFieldFilter($query, TaskField::STATUS->value, $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $this->applyCustomFieldFilter($query, TaskField::PRIORITY->value, $filters['priority']);
        }

        if (! empty($filters['blocked'])) {
            $query->whereHas('dependencies', function (Builder $builder): void {
                $builder->whereNull('tasks.deleted_at');
            });
        }

        return $query;
    }

    /**
     * Guard status changes when dependencies are still open.
     */
    public function saveCustomFieldValue(CustomField $customField, mixed $value, ?Model $tenant = null): void
    {
        if ($customField->code === TaskField::STATUS->value) {
            $label = $this->resolveOptionLabel($customField, $value);

            if ($label === 'Completed' && $this->isBlocked()) {
                throw new \DomainException('Complete dependent tasks first to clear this dependency.');
            }

            // When a task is completed, update percent_complete to 100
            if ($label === 'Completed') {
                $this->percent_complete = 100;
                $this->save();
            }
        }

        $this->baseSaveCustomFieldValue($customField, $value, $tenant);

        // After saving status, update parent task progress if exists
        if ($customField->code === TaskField::STATUS->value && $this->parent_id !== null) {
            $this->parent?->updatePercentComplete();
        }
    }

    /**
     * Validate that status transition is allowed based on dependencies.
     */
    public function canTransitionToStatus(string $statusLabel): bool
    {
        // If trying to complete, check dependencies
        return ! ($statusLabel === 'Completed' && $this->isBlocked());
    }

    /**
     * Schedule a reminder for this task.
     */
    public function scheduleReminder(Carbon $remindAt, User $user, string $channel = 'database'): TaskReminder
    {
        return TaskReminder::create([
            'task_id' => $this->id,
            'user_id' => $user->id,
            'remind_at' => $remindAt,
            'channel' => $channel,
            'status' => 'pending',
        ]);
    }

    /**
     * Cancel all pending reminders for this task.
     */
    public function cancelReminders(): int
    {
        return TaskReminder::query()
            ->where('task_id', $this->id)
            ->whereNull('sent_at')
            ->whereNull('canceled_at')
            ->update([
                'canceled_at' => now(),
                'status' => 'canceled',
            ]);
    }

    /**
     * Get pending reminders for this task.
     *
     * @return \Illuminate\Support\Collection<int, TaskReminder>
     */
    public function getPendingReminders(): \Illuminate\Support\Collection
    {
        return $this->reminders()
            ->where('status', 'pending')
            ->whereNull('sent_at')
            ->whereNull('canceled_at')
            ->oldest('remind_at')
            ->get();
    }

    /**
     * Check if this task has any pending reminders.
     */
    public function hasPendingReminders(): bool
    {
        return $this->reminders()
            ->where('status', 'pending')
            ->whereNull('sent_at')
            ->whereNull('canceled_at')
            ->exists();
    }

    /**
     * Create a recurrence pattern for this task.
     *
     * @param array{frequency: string, interval: int, days_of_week?: array<int>, starts_on?: Carbon, ends_on?: Carbon, max_occurrences?: int, timezone?: string} $pattern
     */
    public function createRecurrence(array $pattern): TaskRecurrence
    {
        return TaskRecurrence::create([
            'task_id' => $this->id,
            'frequency' => $pattern['frequency'],
            'interval' => $pattern['interval'],
            'days_of_week' => $pattern['days_of_week'] ?? null,
            'starts_on' => $pattern['starts_on'] ?? now(),
            'ends_on' => $pattern['ends_on'] ?? null,
            'max_occurrences' => $pattern['max_occurrences'] ?? null,
            'timezone' => $pattern['timezone'] ?? config('app.timezone'),
            'is_active' => true,
        ]);
    }

    /**
     * Update the recurrence pattern for this task.
     *
     * @param array<string, mixed> $pattern
     */
    public function updateRecurrence(array $pattern): bool
    {
        if ($this->recurrence === null) {
            return false;
        }

        return $this->recurrence->update($pattern);
    }

    /**
     * Check if this task is recurring.
     */
    public function isRecurring(): bool
    {
        return $this->recurrence !== null && $this->recurrence->is_active;
    }

    /**
     * Get the next occurrence date for this recurring task.
     */
    public function getNextOccurrenceDate(): ?Carbon
    {
        if (! $this->isRecurring()) {
            return null;
        }

        $recurrence = $this->recurrence;
        $lastDate = $this->start_date ?? now();

        // Get the most recent subtask (instance) if any
        $lastInstance = $this->subtasks()
            ->latest('start_date')
            ->first();

        if ($lastInstance !== null && $lastInstance->start_date !== null) {
            $lastDate = $lastInstance->start_date;
        }

        return match ($recurrence->frequency) {
            'daily' => $lastDate->copy()->addDays($recurrence->interval),
            'weekly' => $lastDate->copy()->addWeeks($recurrence->interval),
            'monthly' => $lastDate->copy()->addMonths($recurrence->interval),
            'yearly' => $lastDate->copy()->addYears($recurrence->interval),
            default => null,
        };
    }

    /**
     * Deactivate the recurrence pattern for this task.
     */
    public function deactivateRecurrence(): bool
    {
        if ($this->recurrence === null) {
            return false;
        }

        return $this->recurrence->update(['is_active' => false]);
    }

    /**
     * Delegate this task to another user.
     */
    public function delegateTo(User $to, User $from, ?string $note = null): TaskDelegation
    {
        $delegation = TaskDelegation::create([
            'task_id' => $this->id,
            'from_user_id' => $from->id,
            'to_user_id' => $to->id,
            'status' => 'pending',
            'delegated_at' => now(),
            'note' => $note,
        ]);

        // Add the delegatee as an assignee if not already assigned
        if (! $this->assignees->contains($to->id)) {
            $this->assignees()->attach($to->id);
        }

        return $delegation;
    }

    /**
     * Get delegation history for this task.
     *
     * @return \Illuminate\Support\Collection<int, TaskDelegation>
     */
    public function getDelegationHistory(): \Illuminate\Support\Collection
    {
        return $this->delegations()
            ->with(['from', 'to'])
            ->latest('delegated_at')
            ->get();
    }

    /**
     * Check if this task has pending delegations.
     */
    public function hasPendingDelegations(): bool
    {
        return $this->delegations()
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Get the latest delegation for this task.
     */
    public function getLatestDelegation(): ?TaskDelegation
    {
        return $this->delegations()
            ->latest('delegated_at')
            ->first();
    }

    /**
     * Mark this task as a milestone.
     */
    public function markAsMilestone(): bool
    {
        return $this->update(['is_milestone' => true]);
    }

    /**
     * Remove milestone status from this task.
     */
    public function unmarkAsMilestone(): bool
    {
        return $this->update(['is_milestone' => false]);
    }

    /**
     * Check if this task is a milestone.
     */
    public function isMilestone(): bool
    {
        return $this->is_milestone === true;
    }

    /**
     * Scope a query to only include milestone tasks.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Task> $query
     *
     * @return \Illuminate\Database\Eloquent\Builder<Task>
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function milestones(Builder $query): Builder
    {
        return $query->where('is_milestone', true);
    }

    /**
     * Scope a query to only include completed milestones.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Task> $query
     *
     * @return \Illuminate\Database\Eloquent\Builder<Task>
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function completedMilestones(Builder $query): Builder
    {
        return $query->where('is_milestone', true)
            ->whereHas('customFieldValues', function (Builder $builder): void {
                $builder->where('custom_field_id', function (Builder $subQuery): void {
                    $subQuery->select('id')
                        ->from('custom_fields')
                        ->where('code', \App\Enums\CustomFields\TaskField::STATUS->value)
                        ->where('entity_type', self::class)
                        ->limit(1);
                })
                    ->whereIn('integer_value', function (Builder $subQuery): void {
                        $subQuery->select('id')
                            ->from('custom_field_options')
                            ->where('name', 'Completed');
                    });
            });
    }

    /**
     * Get milestone completion status for a collection of tasks.
     *
     * @param \Illuminate\Support\Collection<int, Task> $tasks
     *
     * @return array{total: int, completed: int, percentage: float}
     */
    public static function getMilestoneCompletionStatus(\Illuminate\Support\Collection $tasks): array
    {
        $milestones = $tasks->filter(fn (Task $task): bool => $task->isMilestone());
        $total = $milestones->count();

        if ($total === 0) {
            return [
                'total' => 0,
                'completed' => 0,
                'percentage' => 0.0,
            ];
        }

        $completed = $milestones->filter(fn (Task $task): bool => $task->isCompleted())->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => round(($completed / $total) * 100, 2),
        ];
    }

    private function optionLabelForField(string $code): ?string
    {
        $field = $this->resolveCustomField($code);

        if (! $field instanceof CustomField) {
            return null;
        }

        $value = $this->getCustomFieldValue($field);

        return $this->optionLabel($field, $value);
    }

    /**
     * @param Model|\Illuminate\Database\Eloquent\Builder<Task> $query
     * @param array<int|string>                                 $values
     */
    private function applyCustomFieldFilter(Builder $query, string $code, array $values): void
    {
        if ($values === []) {
            return;
        }

        $query->whereHas('customFieldValues', function (Builder $builder) use ($code, $values): void {
            $builder
                ->where('custom_field_id', function (Builder $subQuery) use ($code): void {
                    $subQuery->select('id')
                        ->from('custom_fields')
                        ->where('code', $code)
                        ->where('entity_type', self::class)
                        ->limit(1);
                })
                ->whereIn('integer_value', $values);
        });
    }

    private function resolveCustomField(string $code): ?CustomField
    {
        $tenantId = TenantContextService::getCurrentTenantId() ?? 'global';
        $cacheKey = "{$tenantId}:{$code}";

        if (array_key_exists($cacheKey, self::$customFieldCache)) {
            $cached = self::$customFieldCache[$cacheKey];

            if (! $cached instanceof CustomField) {
                return null;
            }

            if ($cached->exists && CustomField::query()->whereKey($cached->getKey())->exists()) {
                return $cached;
            }

            unset(self::$customFieldCache[$cacheKey]);
        }

        self::$customFieldCache[$cacheKey] = $this->customFields()
            ->where('code', $code)
            ->first();

        return self::$customFieldCache[$cacheKey];
    }

    private function optionLabel(CustomField $field, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $field->loadMissing('options');

        if (is_numeric($value)) {
            $option = $field->options->firstWhere('id', (int) $value);
            if ($option !== null) {
                return $option->name;
            }
        }

        return is_string($value) ? $value : (string) $value;
    }

    private function resolveOptionLabel(CustomField $field, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $field->loadMissing('options');

        if (is_numeric($value)) {
            $option = $field->options->firstWhere('id', (int) $value);

            return $option?->name;
        }

        if (is_string($value)) {
            $option = $field->options->firstWhere('name', $value);

            return $option?->name ?? $value;
        }

        return null;
    }
}
