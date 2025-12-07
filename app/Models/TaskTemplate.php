<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $team_id
 * @property int|null $creator_id
 * @property string $name
 * @property string|null $description
 * @property int|null $estimated_duration_minutes
 * @property bool $is_milestone
 * @property array|null $default_assignees
 * @property array|null $checklist_items
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class TaskTemplate extends Model
{
    use HasCreator;
    use HasFactory;
    use HasTeam;

    protected $fillable = [
        'team_id',
        'creator_id',
        'name',
        'description',
        'estimated_duration_minutes',
        'is_milestone',
        'default_assignees',
        'checklist_items',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'is_milestone' => 'boolean',
            'default_assignees' => 'array',
            'checklist_items' => 'array',
        ];
    }

    /**
     * @return HasMany<Task>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'template_id');
    }

    /**
     * @return BelongsToMany<self>
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'task_template_dependencies',
            'task_template_id',
            'depends_on_template_id'
        )->withTimestamps();
    }

    /**
     * @return BelongsToMany<self>
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'task_template_dependencies',
            'depends_on_template_id',
            'task_template_id'
        )->withTimestamps();
    }

    /**
     * Create a task from this template.
     *
     * @param  array<string, mixed>  $overrides
     */
    public function createTask(array $overrides = []): Task
    {
        $task = Task::create(array_merge([
            'team_id' => $this->team_id,
            'template_id' => $this->id,
            'title' => $this->name,
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            'is_milestone' => $this->is_milestone,
            'percent_complete' => 0,
        ], $overrides));

        // Add default assignees
        if (! empty($this->default_assignees)) {
            $task->assignees()->attach($this->default_assignees);
        }

        // Add checklist items
        if (! empty($this->checklist_items)) {
            foreach ($this->checklist_items as $index => $item) {
                $task->checklistItems()->create([
                    'title' => $item['title'] ?? $item,
                    'position' => $index,
                    'is_completed' => false,
                ]);
            }
        }

        return $task;
    }
}
