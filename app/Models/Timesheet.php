<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TimesheetStatus;
use App\Models\Concerns\HasTeam;
use Database\Factories\TimesheetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int                             $id
 * @property int                             $team_id
 * @property int                             $employee_id
 * @property int|null                        $manager_id
 * @property \Illuminate\Support\Carbon      $period_start
 * @property \Illuminate\Support\Carbon      $period_end
 * @property TimesheetStatus                 $status
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property int|null                        $approved_by
 * @property \Illuminate\Support\Carbon|null $rejected_at
 * @property int|null                        $rejected_by
 * @property string|null                     $rejection_reason
 * @property \Illuminate\Support\Carbon|null $locked_at
 * @property int|null                        $locked_by
 * @property \Illuminate\Support\Carbon|null $deadline_at
 * @property \Illuminate\Support\Carbon|null $reminder_24h_sent_at
 * @property \Illuminate\Support\Carbon|null $reminder_deadline_day_sent_at
 * @property \Illuminate\Support\Carbon|null $auto_submitted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class Timesheet extends Model
{
    /** @use HasFactory<TimesheetFactory> */
    use HasFactory;

    use HasTeam;

    protected $fillable = [
        'team_id',
        'employee_id',
        'manager_id',
        'period_start',
        'period_end',
        'status',
        'submitted_at',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'locked_at',
        'locked_by',
        'deadline_at',
        'reminder_24h_sent_at',
        'reminder_deadline_day_sent_at',
        'auto_submitted_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => TimesheetStatus::DRAFT,
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'status' => TimesheetStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'locked_at' => 'datetime',
            'deadline_at' => 'datetime',
            'reminder_24h_sent_at' => 'datetime',
            'reminder_deadline_day_sent_at' => 'datetime',
            'auto_submitted_at' => 'datetime',
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
     * @return BelongsTo<Employee, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return HasMany<TimeEntry, $this>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }
}
