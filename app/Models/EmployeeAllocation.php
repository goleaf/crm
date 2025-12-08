<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $employee_id
 * @property string $allocatable_type
 * @property int $allocatable_id
 * @property float $allocation_percentage
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class EmployeeAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'allocatable_type',
        'allocatable_id',
        'allocation_percentage',
        'start_date',
        'end_date',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'allocation_percentage' => 0,
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'allocation_percentage' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function allocatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if allocation is currently active.
     */
    public function isActive(): bool
    {
        $now = now();

        $startValid = $this->start_date === null || $this->start_date->lte($now);
        $endValid = $this->end_date === null || $this->end_date->gte($now);

        return $startValid && $endValid;
    }

    /**
     * Check if allocation overlaps with a given period.
     */
    public function overlapsWith(\Illuminate\Support\Carbon $startDate, \Illuminate\Support\Carbon $endDate): bool
    {
        // If allocation has no dates, it's always active
        if ($this->start_date === null && $this->end_date === null) {
            return true;
        }

        // Check for overlap
        $allocationStart = $this->start_date ?? $startDate;
        $allocationEnd = $this->end_date ?? $endDate;

        return $allocationStart->lte($endDate) && $allocationEnd->gte($startDate);
    }
}
