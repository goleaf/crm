<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TaskTimeEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TaskTimeEntry extends Model
{
    /** @use HasFactory<TaskTimeEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'started_at',
        'ended_at',
        'duration_minutes',
        'is_billable',
        'billing_rate',
        'note',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_billable' => 'boolean',
        'billing_rate' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (self $timeEntry): void {
            $timeEntry->validateNoOverlap();
            $timeEntry->validateNoDuplicate();
        });

        self::updating(function (self $timeEntry): void {
            $timeEntry->validateNoOverlap();
        });
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Validate that this time entry doesn't overlap with existing entries for the same user.
     *
     * @throws \DomainException
     */
    public function validateNoOverlap(): void
    {
        if ($this->started_at === null || $this->ended_at === null) {
            return;
        }

        $query = self::where('user_id', $this->user_id)
            ->whereNotNull('started_at')
            ->whereNotNull('ended_at')
            ->where(function ($q): void {
                $q->whereBetween('started_at', [$this->started_at, $this->ended_at])
                    ->orWhereBetween('ended_at', [$this->started_at, $this->ended_at])
                    ->orWhere(function ($q): void {
                        $q->where('started_at', '<=', $this->started_at)
                            ->where('ended_at', '>=', $this->ended_at);
                    });
            });

        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }

        if ($query->exists()) {
            throw new \DomainException('Time entry overlaps with an existing entry for this user.');
        }
    }

    /**
     * Validate that this exact time entry doesn't already exist.
     *
     * @throws \DomainException
     */
    public function validateNoDuplicate(): void
    {
        $query = self::where('task_id', $this->task_id)
            ->where('user_id', $this->user_id)
            ->where('started_at', $this->started_at)
            ->where('duration_minutes', $this->duration_minutes);

        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }

        if ($query->exists()) {
            throw new \DomainException('This time entry already exists.');
        }
    }
}
