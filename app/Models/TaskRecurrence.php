<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TaskRecurrenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TaskRecurrence extends Model
{
    /** @use HasFactory<TaskRecurrenceFactory> */
    use HasFactory;

    protected $fillable = [
        'task_id',
        'frequency',
        'interval',
        'days_of_week',
        'starts_on',
        'ends_on',
        'max_occurrences',
        'timezone',
        'is_active',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'days_of_week' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
