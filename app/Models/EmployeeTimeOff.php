<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TimeOffStatus;
use App\Enums\TimeOffType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int                             $id
 * @property int                             $employee_id
 * @property int|null                        $approved_by
 * @property TimeOffType                     $type
 * @property \Illuminate\Support\Carbon      $start_date
 * @property \Illuminate\Support\Carbon      $end_date
 * @property float                           $days
 * @property TimeOffStatus                   $status
 * @property string|null                     $reason
 * @property string|null                     $rejection_reason
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class EmployeeTimeOff extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'approved_by',
        'type',
        'start_date',
        'end_date',
        'days',
        'status',
        'reason',
        'rejection_reason',
        'approved_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => TimeOffStatus::PENDING,
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'type' => TimeOffType::class,
            'status' => TimeOffStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'days' => 'decimal:2',
            'approved_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Approve the time off request.
     */
    public function approve(User $approver): void
    {
        $this->update([
            'status' => TimeOffStatus::APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        // Update employee's used days
        $employee = $this->employee;

        if ($this->type === TimeOffType::VACATION) {
            $employee->vacation_days_used += $this->days;
            $employee->save();
        } elseif ($this->type === TimeOffType::SICK) {
            $employee->sick_days_used += $this->days;
            $employee->save();
        }
    }

    /**
     * Reject the time off request.
     */
    public function reject(User $approver, string $reason): void
    {
        $this->update([
            'status' => TimeOffStatus::REJECTED,
            'approved_by' => $approver->id,
            'rejection_reason' => $reason,
            'approved_at' => now(),
        ]);
    }

    /**
     * Cancel the time off request.
     */
    public function cancel(): void
    {
        if ($this->status === TimeOffStatus::APPROVED) {
            // Restore used days
            $employee = $this->employee;

            if ($this->type === TimeOffType::VACATION) {
                $employee->vacation_days_used = max(0, $employee->vacation_days_used - $this->days);
                $employee->save();
            } elseif ($this->type === TimeOffType::SICK) {
                $employee->sick_days_used = max(0, $employee->sick_days_used - $this->days);
                $employee->save();
            }
        }

        $this->update(['status' => TimeOffStatus::CANCELLED]);
    }

    /**
     * Check if request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === TimeOffStatus::PENDING;
    }

    /**
     * Check if request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === TimeOffStatus::APPROVED;
    }
}
