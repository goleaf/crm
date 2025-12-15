<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Database\Factories\LeaveBalanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LeaveBalance extends Model
{
    /** @use HasFactory<LeaveBalanceFactory> */
    use HasFactory;

    use HasTeam;

    protected $fillable = [
        'team_id',
        'employee_id',
        'leave_type_id',
        'year',
        'allocated_days',
        'used_days',
        'pending_days',
        'available_days',
        'carried_over_days',
        'expires_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'allocated_days' => 0,
        'used_days' => 0,
        'pending_days' => 0,
        'available_days' => 0,
        'carried_over_days' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'allocated_days' => 'decimal:2',
            'used_days' => 'decimal:2',
            'pending_days' => 'decimal:2',
            'available_days' => 'decimal:2',
            'carried_over_days' => 'decimal:2',
            'expires_at' => 'date',
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
     * @return BelongsTo<LeaveType, $this>
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function recalculate(): void
    {
        $this->available_days = (float) $this->allocated_days
            + (float) $this->carried_over_days
            - (float) $this->used_days
            - (float) $this->pending_days;
    }

    public function deduct(float $days): void
    {
        $this->used_days = (float) $this->used_days + $days;
        $this->recalculate();
    }

    public function restore(float $days): void
    {
        $this->used_days = (float) $this->used_days - $days;
        $this->recalculate();
    }

    public function hasAvailableBalance(float $days): bool
    {
        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return (float) $this->available_days >= $days;
    }
}

