<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreationSource;
use App\Enums\CustomFields\TaskField;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasNotes;
use App\Models\Concerns\HasTeam;
use App\Models\Concerns\InvalidatesRelatedAiSummaries;
use App\Observers\TaskObserver;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
 * @property int $id
 * @property Carbon|null $deleted_at
 * @property CreationSource $creation_source
 * @property string $createdBy
 *
 * @method void saveCustomFieldValue(CustomField $field, mixed $value, ?Model $tenant = null)
 */
#[ObservedBy(TaskObserver::class)]
final class Task extends Model implements HasCustomFields
{
    use HasCreator;

    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    use HasNotes;
    use HasTeam;
    use InvalidatesRelatedAiSummaries;
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
     * @return HasMany<self>
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
     * @return BelongsToMany<self>
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'task_dependencies',
            'task_id',
            'depends_on_task_id'
        )->withTimestamps();
    }

    /**
     * @return BelongsToMany<self>
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'task_dependencies',
            'depends_on_task_id',
            'task_id'
        )->withTimestamps();
    }

    /**
     * @return BelongsToMany<TaskCategory, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(TaskCategory::class, 'task_task_category')
            ->withTimestamps();
    }

    /**
     * @return HasMany<TaskChecklistItem>
     */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(TaskChecklistItem::class)->orderBy('position');
    }

    /**
     * @return HasMany<TaskComment>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    /**
     * @return HasMany<TaskTimeEntry>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TaskTimeEntry::class)->latest();
    }

    /**
     * @return HasMany<TaskReminder>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(TaskReminder::class)->latest('remind_at');
    }

    /**
     * @return HasOne<TaskRecurrence>
     */
    public function recurrence(): HasOne
    {
        return $this->hasOne(TaskRecurrence::class);
    }

    /**
     * @return HasMany<TaskDelegation>
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
     */
    public function calculatePercentComplete(): float
    {
        $subtasks = $this->subtasks()->get();

        if ($subtasks->isEmpty()) {
            return (float) $this->percent_complete;
        }

        $totalSubtasks = $subtasks->count();
        $totalProgress = $subtasks->sum(fn (Task $task): float => $task->calculatePercentComplete());

        return round($totalProgress / $totalSubtasks, 2);
    }

    /**
     * Update the percent_complete field based on subtasks or completion status.
     */
    public function updatePercentComplete(): void
    {
        $this->percent_complete = $this->isCompleted() ? 100 : $this->calculatePercentComplete();

        $this->save();

        // Update parent task if exists
        if ($this->parent_id !== null) {
            $this->parent?->updatePercentComplete();
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
            return Carbon::instance($value);
        }

        if (is_string($value)) {
            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    /**
     * Apply common task filters for list views.
     *
     * @param  array{assignees?: array<int>, categories?: array<int>, status?: array<int|string>, priority?: array<int|string>, blocked?: bool}  $filters
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
        }

        $this->baseSaveCustomFieldValue($customField, $value, $tenant);
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
     * @param  Model|\Illuminate\Database\Eloquent\Builder<Task>  $query
     * @param  array<int|string>  $values
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
