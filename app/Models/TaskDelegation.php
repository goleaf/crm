<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TaskDelegationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TaskDelegation extends Model
{
    /** @use HasFactory<TaskDelegationFactory> */
    use HasFactory;

    protected $fillable = [
        'task_id',
        'from_user_id',
        'to_user_id',
        'status',
        'delegated_at',
        'accepted_at',
        'declined_at',
        'note',
    ];

    protected $casts = [
        'delegated_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
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
    public function from(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function to(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
