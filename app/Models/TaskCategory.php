<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Database\Factories\TaskCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int         $id
 * @property string      $name
 * @property string|null $color
 */
final class TaskCategory extends Model
{
    /** @use HasFactory<TaskCategoryFactory> */
    use HasFactory;

    use HasTeam;

    protected $fillable = [
        'team_id',
        'name',
        'color',
    ];

    protected static function booted(): void
    {
        self::creating(function (TaskCategory $category): void {
            if ($category->team_id === null && auth('web')->check()) {
                /** @var \App\Models\User $user */
                $user = auth('web')->user();
                $category->team_id = $user->currentTeam->getKey();
            }
        });
    }

    /**
     * @return BelongsToMany<Task, $this>
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_task_category')
            ->withTimestamps();
    }
}
