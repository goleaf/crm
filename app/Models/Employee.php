<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Models\Concerns\HasTeam;
use HosmelQ\NameOfPerson\PersonName;
use HosmelQ\NameOfPerson\PersonNameCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $team_id
 * @property int|null $user_id
 * @property int|null $manager_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $mobile
 * @property string|null $employee_number
 * @property string|null $department
 * @property string|null $role
 * @property string|null $title
 * @property EmployeeStatus $status
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postal_code
 * @property string|null $country
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property string|null $emergency_contact_relationship
 * @property array|null $skills
 * @property array|null $certifications
 * @property string|null $performance_notes
 * @property float|null $performance_rating
 * @property float $vacation_days_total
 * @property float $vacation_days_used
 * @property float $sick_days_total
 * @property float $sick_days_used
 * @property bool $has_portal_access
 * @property string|null $payroll_id
 * @property array|null $payroll_metadata
 * @property float $capacity_hours_per_week
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class Employee extends Model implements HasMedia
{
    use HasFactory;
    use HasTeam;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'team_id',
        'user_id',
        'manager_id',
        'name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'mobile',
        'employee_number',
        'department',
        'role',
        'title',
        'status',
        'start_date',
        'end_date',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'skills',
        'certifications',
        'performance_notes',
        'performance_rating',
        'vacation_days_total',
        'vacation_days_used',
        'sick_days_total',
        'sick_days_used',
        'has_portal_access',
        'payroll_id',
        'payroll_metadata',
        'capacity_hours_per_week',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
        'vacation_days_total' => 0,
        'vacation_days_used' => 0,
        'sick_days_total' => 0,
        'sick_days_used' => 0,
        'has_portal_access' => false,
        'capacity_hours_per_week' => 40,
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status' => EmployeeStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'skills' => 'array',
            'certifications' => 'array',
            'performance_rating' => 'decimal:2',
            'vacation_days_total' => 'decimal:2',
            'vacation_days_used' => 'decimal:2',
            'sick_days_total' => 'decimal:2',
            'sick_days_used' => 'decimal:2',
            'has_portal_access' => 'boolean',
            'payroll_metadata' => 'array',
            'capacity_hours_per_week' => 'decimal:2',
            'name' => PersonNameCast::using('first_name', 'last_name'),
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(static function (self $employee): void {
            if ($employee->team_id === null && auth()->check() && auth()->user()?->currentTeam !== null) {
                $employee->team_id = auth()->user()->currentTeam->getKey();
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Employee, $this>
     */
    public function directReports(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\EmployeeDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\EmployeeTimeOff, $this>
     */
    public function timeOffRequests(): HasMany
    {
        return $this->hasMany(EmployeeTimeOff::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\EmployeeAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(EmployeeAllocation::class);
    }

    /**
     * @return MorphToMany<Project, $this>
     */
    public function projects(): MorphToMany
    {
        return $this->morphedByMany(Project::class, 'allocatable', 'employee_allocations');
    }

    /**
     * @return MorphToMany<Task, $this>
     */
    public function tasks(): MorphToMany
    {
        return $this->morphedByMany(Task::class, 'allocatable', 'employee_allocations');
    }

    /**
     * Get the employee's full name.
     */
    protected function getFullNameAttribute(): string
    {
        $name = $this->name;

        if ($name instanceof PersonName) {
            return $name->full();
        }

        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get remaining vacation days.
     */
    protected function getRemainingVacationDaysAttribute(): float
    {
        return max(0, $this->vacation_days_total - $this->vacation_days_used);
    }

    /**
     * Get remaining sick days.
     */
    protected function getRemainingSickDaysAttribute(): float
    {
        return max(0, $this->sick_days_total - $this->sick_days_used);
    }

    /**
     * Check if employee is active.
     */
    public function isActive(): bool
    {
        return $this->status === EmployeeStatus::ACTIVE;
    }

    /**
     * Check if employee is over-allocated.
     * Returns true if total allocation exceeds capacity.
     */
    public function isOverAllocated(?\Illuminate\Support\Carbon $startDate = null, ?\Illuminate\Support\Carbon $endDate = null): bool
    {
        $totalAllocation = $this->getTotalAllocation($startDate, $endDate);

        return $totalAllocation > 100;
    }

    /**
     * Get total allocation percentage for a given period.
     * If no dates provided, checks current active allocations.
     */
    public function getTotalAllocation(?\Illuminate\Support\Carbon $startDate = null, ?\Illuminate\Support\Carbon $endDate = null): float
    {
        $query = $this->allocations();

        if ($startDate instanceof \Illuminate\Support\Carbon && $endDate instanceof \Illuminate\Support\Carbon) {
            $query->where(function (\Illuminate\Contracts\Database\Query\Builder $q) use ($startDate, $endDate): void {
                $q->where(function (\Illuminate\Contracts\Database\Query\Builder $subQ) use ($startDate, $endDate): void {
                    // Allocation overlaps with the period
                    $subQ->where('start_date', '<=', $endDate)
                        ->where(function (\Illuminate\Contracts\Database\Query\Builder $dateQ) use ($startDate): void {
                            $dateQ->where('end_date', '>=', $startDate)
                                ->orWhereNull('end_date');
                        });
                })
                    ->orWhere(function (\Illuminate\Contracts\Database\Query\Builder $subQ): void {
                        // Open-ended allocations
                        $subQ->whereNull('start_date')
                            ->whereNull('end_date');
                    });
            });
        } elseif (! $startDate instanceof \Illuminate\Support\Carbon && ! $endDate instanceof \Illuminate\Support\Carbon) {
            // Current allocations (no end date or end date in future)
            $query->where(function (\Illuminate\Contracts\Database\Query\Builder $q): void {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
        }

        return (float) $query->sum('allocation_percentage');
    }

    /**
     * Get available capacity percentage for a given period.
     */
    public function getAvailableCapacity(?\Illuminate\Support\Carbon $startDate = null, ?\Illuminate\Support\Carbon $endDate = null): float
    {
        $totalAllocation = $this->getTotalAllocation($startDate, $endDate);

        return max(0, 100 - $totalAllocation);
    }

    /**
     * Allocate employee to a project or task.
     *
     * @param  Project|Task  $allocatable
     */
    public function allocateTo(Model $allocatable, float $percentage, ?\Illuminate\Support\Carbon $startDate = null, ?\Illuminate\Support\Carbon $endDate = null): EmployeeAllocation
    {
        // Check if allocation would exceed capacity
        $currentAllocation = $this->getTotalAllocation($startDate, $endDate);

        if ($currentAllocation + $percentage > 100) {
            throw new \DomainException(
                "Cannot allocate {$percentage}% - would exceed capacity. Current allocation: {$currentAllocation}%, Available: ".(100 - $currentAllocation).'%'
            );
        }

        return $this->allocations()->create([
            'allocatable_type' => $allocatable::class,
            'allocatable_id' => $allocatable->id,
            'allocation_percentage' => $percentage,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Request time off.
     */
    public function requestTimeOff(string $type, \Illuminate\Support\Carbon $startDate, \Illuminate\Support\Carbon $endDate, ?string $reason = null): EmployeeTimeOff
    {
        $days = $startDate->diffInDays($endDate) + 1;

        return $this->timeOffRequests()->create([
            'type' => $type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days' => $days,
            'status' => 'pending',
            'reason' => $reason,
        ]);
    }

    /**
     * Check if employee is on time off during a given period.
     */
    public function isOnTimeOff(\Illuminate\Support\Carbon $startDate, \Illuminate\Support\Carbon $endDate): bool
    {
        return $this->timeOffRequests()
            ->where('status', 'approved')
            ->where(function (\Illuminate\Contracts\Database\Query\Builder $query) use ($startDate, $endDate): void {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function (\Illuminate\Contracts\Database\Query\Builder $q) use ($startDate, $endDate): void {
                        $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->exists();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_photo')
            ->singleFile()
            ->useDisk(config('filesystems.default', 'public'));

        $this->addMediaCollection('documents')
            ->useDisk(config('filesystems.default', 'public'));
    }
}
