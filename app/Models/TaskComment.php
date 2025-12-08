<?php

declare(strict_types=1);

namespace App\Models;

use Binafy\LaravelReaction\Contracts\HasReaction;
use Binafy\LaravelReaction\Traits\Reactable;
use Database\Factories\TaskCommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TaskComment extends Model implements HasReaction
{
    /** @use HasFactory<TaskCommentFactory> */
    use HasFactory;

    use Reactable;

    protected $fillable = [
        'task_id',
        'user_id',
        'parent_id',
        'body',
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

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\TaskComment, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
