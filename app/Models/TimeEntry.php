<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreationSource;
use App\Enums\TimeEntryApprovalStatus;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use App\Observers\TimeEntryObserver;
use Database\Factories\TimeEntryFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;

#[ObservedBy(TimeEntryObserver::class)]
final class TimeEntry extends Model
{
    use HasCreator;
    use HasTeam;
    use SoftDeletes;

    /** @use HasFactory<TimeEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'team_id',
        'employee_id',
        'date',
        'start_time',
        'end_time',
        'duration_minutes',
        'description',
        'notes',
        'is_billable',
        'billing_rate',
        'billing_amount',
        'project_id',
        'task_id',
        'company_id',
        'time_category_id',
        'timesheet_id',
        'approval_status',
        'approved_by',
        'approved_at',
        'invoice_id',
        'creator_id',
        'editor_id',
        'deleted_by',
        'creation_source',
        'timezone',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_billable' => false,
        'approval_status' => TimeEntryApprovalStatus::PENDING,
        'creation_source' => CreationSource::WEB,
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'duration_minutes' => 'integer',
            'is_billable' => 'boolean',
            'billing_rate' => 'decimal:2',
            'billing_amount' => 'decimal:2',
            'approval_status' => TimeEntryApprovalStatus::class,
            'approved_at' => 'datetime',
            'creation_source' => CreationSource::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(static function (self $entry): void {
            $entry->syncDuration();
            $entry->syncBillingAmount();
            $entry->validateInvariants();
            $entry->validateNoOverlap();
        });
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<TimeCategory, $this>
     */
    public function timeCategory(): BelongsTo
    {
        return $this->belongsTo(TimeCategory::class);
    }

    /**
     * @return BelongsTo<Timesheet, $this>
     */
    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(Timesheet::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @param Builder<self> $query
     */
    public function scopeBillable(Builder $query): void
    {
        $query->where('is_billable', true);
    }

    /**
     * @param Builder<self> $query
     */
    public function scopeUnbilled(Builder $query): void
    {
        $query->where('is_billable', true)->whereNull('invoice_id');
    }

    /**
     * @param Builder<self> $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('approval_status', TimeEntryApprovalStatus::PENDING);
    }

    /**
     * @param Builder<self> $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('approval_status', TimeEntryApprovalStatus::APPROVED);
    }

    /**
     * @param Builder<self> $query
     */
    public function scopeForDateRange(Builder $query, Carbon $start, Carbon $end): void
    {
        $query->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
    }

    public function calculateDuration(): int
    {
        if ($this->start_time !== null && $this->end_time !== null) {
            return $this->end_time->diffInMinutes($this->start_time);
        }

        return (int) $this->duration_minutes;
    }

    public function calculateBillingAmount(): float
    {
        if (! $this->is_billable) {
            return 0.0;
        }

        if ($this->billing_rate === null) {
            throw new \DomainException('Billing rate is required for billable time entries.');
        }

        return (float) round(($this->duration_minutes / 60) * (float) $this->billing_rate, 2);
    }

    public function canBeEdited(?User $actor = null): bool
    {
        if ($this->approval_status !== TimeEntryApprovalStatus::APPROVED) {
            return true;
        }

        if (! (bool) config('time-management.time_entries.approval.lock_approved', true)) {
            return true;
        }

        if (! $actor instanceof User) {
            return false;
        }

        return $actor->hasAnyRole(config('permission.defaults.super_admin_roles', ['admin']));
    }

    public function canBeDeleted(?User $actor = null): bool
    {
        return $this->canBeEdited($actor);
    }

    public function requiresApproval(): bool
    {
        $cutoffDays = (int) config('time-management.time_entries.approval.past_date_cutoff_days', 0);

        if ($cutoffDays < 1) {
            return false;
        }

        $cutoffDate = Date::now()->subDays($cutoffDays)->startOfDay();

        return Date::parse($this->date)->startOfDay()->lessThan($cutoffDate);
    }

    private function syncDuration(): void
    {
        if ($this->start_time === null || $this->end_time === null) {
            return;
        }

        if ($this->end_time->lessThanOrEqualTo($this->start_time)) {
            throw new \DomainException('End time must be after start time.');
        }

        $this->duration_minutes = $this->end_time->diffInMinutes($this->start_time);
    }

    private function syncBillingAmount(): void
    {
        if (! $this->is_billable) {
            $this->billing_amount = null;

            return;
        }

        if ($this->billing_rate === null) {
            return;
        }

        $this->billing_amount = round(($this->duration_minutes / 60) * (float) $this->billing_rate, 2);
    }

    private function validateInvariants(): void
    {
        if (! is_int($this->duration_minutes)) {
            $this->duration_minutes = (int) $this->duration_minutes;
        }

        $minDuration = ($this->start_time !== null && $this->end_time === null) ? 0 : 1;
        if ($this->duration_minutes < $minDuration || $this->duration_minutes > 1440) {
            throw new \DomainException('Duration must be between 0 minutes and 24 hours.');
        }

        if ($this->project_id === null && $this->task_id === null) {
            throw new \DomainException('Time entries must be associated with a project or task.');
        }

        if ($this->is_billable) {
            if ($this->project_id === null) {
                throw new \DomainException('Billable time entries must be associated with a project.');
            }

            if ($this->billing_rate === null || (float) $this->billing_rate <= 0) {
                throw new \DomainException('Billing rate is required for billable time entries.');
            }
        }

        if ($this->time_category_id !== null) {
            $category = TimeCategory::query()->find($this->time_category_id);

            if ($category instanceof TimeCategory && ! $category->is_active) {
                $isAllowedHistorical = $this->exists && ! $this->isDirty('time_category_id');

                if (! $isAllowedHistorical) {
                    throw new \DomainException('Selected job role is inactive.');
                }
            }
        }

        if ($this->project_id !== null && $this->task_id !== null) {
            $isLinked = DB::table('project_task')
                ->where('project_id', $this->project_id)
                ->where('task_id', $this->task_id)
                ->exists();

            if (! $isLinked) {
                throw new \DomainException('Task must belong to the selected project.');
            }
        }

        if ($this->description !== null && mb_strlen($this->description) > 1000) {
            throw new \DomainException('Description may not be greater than 1000 characters.');
        }
    }

    private function validateNoOverlap(): void
    {
        if ($this->start_time === null) {
            return;
        }

        $start = $this->start_time;
        $end = $this->end_time;

        $query = self::query()
            ->where('employee_id', $this->employee_id)
            ->whereDate('date', Date::parse($this->date)->toDateString())
            ->whereNotNull('start_time')
            ->when($this->exists, fn (Builder $builder): Builder => $builder->whereKeyNot($this->getKey()));

        if ($end === null) {
            $query->where('start_time', '<=', $start)
                ->where(function (Builder $builder) use ($start): void {
                    $builder
                        ->whereNull('end_time')
                        ->orWhere('end_time', '>', $start);
                });
        } else {
            $query->where('start_time', '<', $end)
                ->where(function (Builder $builder) use ($start): void {
                    $builder
                        ->whereNull('end_time')
                        ->orWhere('end_time', '>', $start);
                });
        }

        if ($query->exists()) {
            throw new \DomainException('Time entry overlaps with an existing entry for this employee.');
        }
    }
}
