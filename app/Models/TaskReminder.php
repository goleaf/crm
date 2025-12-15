<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TaskReminderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TaskReminder extends Model
{
    /** @use HasFactory<TaskReminderFactory> */
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'remind_at',
        'sent_at',
        'canceled_at',
        'channel',
        'status',
    ];

    protected $casts = [
        'remind_at' => 'datetime',
        'sent_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

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
}
