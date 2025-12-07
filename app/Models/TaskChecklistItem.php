<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TaskChecklistItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property bool $is_completed
 */
final class TaskChecklistItem extends Model
{
    /** @use HasFactory<TaskChecklistItemFactory> */
    use HasFactory;

    protected $fillable = [
        'task_id',
        'title',
        'is_completed',
        'position',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
    ];

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
