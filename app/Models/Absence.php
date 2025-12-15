<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AbsenceStatus;
use App\Enums\CreationSource;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use Database\Factories\AbsenceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Carbon\CarbonPeriod;

final class Absence extends Model
{
    use HasCreator;

    /** @use HasFactory<AbsenceFactory> */
    use HasFactory;

    use HasTeam;
    use SoftDeletes;

    protected $fillable = [
        'team_id',
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'duration_days',
        'duration_hours',
        'status',
        'reason',
        'notes',
        'approved_by',
        'approved_at',
        'rejected_reason',
        'creation_source',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => AbsenceStatus::PENDING,
        'creation_source' => CreationSource::WEB,
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'duration_days' => 'decimal:2',
            'duration_hours' => 'decimal:2',
            'status' => AbsenceStatus::class,
            'approved_at' => 'datetime',
            'creation_source' => CreationSource::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(static function (self $absence): void {
            $absence->syncDuration();
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
     * @return BelongsTo<LeaveType, $this>
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @param Builder<self> $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', AbsenceStatus::PENDING);
    }

    /**
     * @param Builder<self> $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', AbsenceStatus::APPROVED);
    }

    /**
     * @param Builder<self> $query
     */
    public function scopeRejected(Builder $query): void
    {
        $query->where('status', AbsenceStatus::REJECTED);
    }

    /**
     * @param Builder<self> $query
     */
    public function scopeForDateRange(Builder $query, Carbon $start, Carbon $end): void
    {
        $query->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString());
    }

    /**
     * @param Builder<self> $query
     */
    public function scopeOverlapping(Builder $query, Carbon $start, Carbon $end): void
    {
        $query->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString());
    }

    /**
     * @return array{days: float, hours: float}
     */
    public function calculateDuration(): array
    {
        $start = Date::parse($this->start_date)->startOfDay();
        $end = Date::parse($this->end_date)->startOfDay();

        if ($end->lessThan($start)) {
            throw new \DomainException('End date must be on or after start date.');
        }

        $businessHours = config('laravel-crm.business_hours', []);

        $holidays = array_values(array_unique(array_merge(
            (array) data_get($businessHours, 'holidays', []),
            (array) config('time-management.absences.holidays', []),
        )));

        $totalBusinessDays = 0;
        $totalBusinessHours = 0.0;

        /** @var Carbon $day */
        foreach (CarbonPeriod::create($start, '1 day', $end) as $day) {
            $dateKey = $day->toDateString();

            if (in_array($dateKey, $holidays, true)) {
                continue;
            }

            $weekdayKey = strtolower($day->englishDayOfWeek);
            $dayHours = data_get($businessHours, $weekdayKey);

            if (! is_array($dayHours) || ! isset($dayHours['start'], $dayHours['end'])) {
                continue;
            }

            $dayStart = Date::parse($dateKey . ' ' . $dayHours['start']);
            $dayEnd = Date::parse($dateKey . ' ' . $dayHours['end']);

            if ($dayEnd->lessThanOrEqualTo($dayStart)) {
                continue;
            }

            $totalBusinessDays += 1;
            $totalBusinessHours += round($dayEnd->diffInMinutes($dayStart) / 60, 2);
        }

        return [
            'days' => (float) $totalBusinessDays,
            'hours' => (float) round($totalBusinessHours, 2),
        ];
    }

    public function overlapsWithExisting(): bool
    {
        $start = Date::parse($this->start_date)->startOfDay();
        $end = Date::parse($this->end_date)->startOfDay();

        return self::query()
            ->where('employee_id', $this->employee_id)
            ->when($this->exists, fn (Builder $query): Builder => $query->whereKeyNot($this->getKey()))
            ->overlapping($start, $end)
            ->exists();
    }

    public function canBeApproved(): bool
    {
        return $this->status === AbsenceStatus::PENDING;
    }

    public function canBeCancelled(): bool
    {
        return $this->status !== AbsenceStatus::CANCELLED;
    }

    private function syncDuration(): void
    {
        $duration = $this->calculateDuration();

        $this->duration_days = $duration['days'];
        $this->duration_hours = $duration['hours'];
    }
}
