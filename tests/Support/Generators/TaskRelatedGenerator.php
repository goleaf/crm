<?php

declare(strict_types=1);

namespace Tests\Support\Generators;

use App\Models\Task;
use App\Models\TaskChecklistItem;
use App\Models\TaskComment;
use App\Models\TaskDelegation;
use App\Models\TaskRecurrence;
use App\Models\TaskReminder;
use App\Models\TaskTimeEntry;
use App\Models\User;

/**
 * Generator for creating task-related entities for property-based testing.
 */
final class TaskRelatedGenerator
{
    /**
     * Generate a task reminder.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function generateReminder(Task $task, ?User $user = null, array $overrides = []): TaskReminder
    {
        $user ??= User::factory()->create();

        $data = array_merge([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'remind_at' => fake()->dateTimeBetween('now', '+1 week'),
            'channel' => fake()->randomElement(['mail', 'database', 'slack']),
            'status' => fake()->randomElement(['pending', 'sent', 'failed', 'canceled']),
        ], $overrides);

        return TaskReminder::factory()->create($data);
    }

    /**
     * Generate a task recurrence pattern.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function generateRecurrence(Task $task, array $overrides = []): TaskRecurrence
    {
        $data = array_merge([
            'task_id' => $task->id,
            'frequency' => fake()->randomElement(['daily', 'weekly', 'monthly', 'yearly']),
            'interval' => fake()->numberBetween(1, 4),
            'days_of_week' => fake()->optional()->randomElements([0, 1, 2, 3, 4, 5, 6], fake()->numberBetween(1, 3)),
            'starts_on' => \Illuminate\Support\Facades\Date::now(),
            'ends_on' => fake()->optional()->dateTimeBetween('+1 month', '+1 year'),
            'max_occurrences' => fake()->optional()->numberBetween(5, 50),
            'timezone' => fake()->timezone(),
            'is_active' => true,
        ], $overrides);

        return TaskRecurrence::factory()->create($data);
    }

    /**
     * Generate a task delegation.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function generateDelegation(
        Task $task,
        User $fromUser,
        User $toUser,
        array $overrides = []
    ): TaskDelegation {
        $data = array_merge([
            'task_id' => $task->id,
            'from_user_id' => $fromUser->id,
            'to_user_id' => $toUser->id,
            'status' => fake()->randomElement(['pending', 'accepted', 'declined']),
            'delegated_at' => \Illuminate\Support\Facades\Date::now(),
            'note' => fake()->optional()->sentence(),
        ], $overrides);

        return TaskDelegation::factory()->create($data);
    }

    /**
     * Generate a task checklist item.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function generateChecklistItem(Task $task, array $overrides = []): TaskChecklistItem
    {
        $data = array_merge([
            'task_id' => $task->id,
            'title' => fake()->sentence(),
            'is_completed' => fake()->boolean(),
            'position' => fake()->numberBetween(1, 10),
        ], $overrides);

        return TaskChecklistItem::factory()->create($data);
    }

    /**
     * Generate multiple checklist items.
     *
     * @param  int  $count  Number of items to generate
     * @return array<TaskChecklistItem>
     */
    public static function generateChecklistItems(Task $task, int $count = 3): array
    {
        $items = [];

        for ($i = 0; $i < $count; $i++) {
            $items[] = self::generateChecklistItem($task, ['position' => $i + 1]);
        }

        return $items;
    }

    /**
     * Generate a task comment.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function generateComment(Task $task, ?User $user = null, array $overrides = []): TaskComment
    {
        $user ??= User::factory()->create();

        $data = array_merge([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'body' => fake()->paragraph(),
        ], $overrides);

        return TaskComment::factory()->create($data);
    }

    /**
     * Generate a task time entry.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function generateTimeEntry(Task $task, ?User $user = null, array $overrides = []): TaskTimeEntry
    {
        $user ??= User::factory()->create();

        $startedAt = \Illuminate\Support\Facades\Date::parse(fake()->dateTimeBetween('-1 week', 'now'));
        $durationMinutes = fake()->numberBetween(15, 480);
        $endedAt = $startedAt->copy()->addMinutes($durationMinutes);

        $data = array_merge([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_minutes' => $durationMinutes,
            'is_billable' => fake()->boolean(70), // 70% chance of being billable
            'billing_rate' => fake()->optional(0.7)->randomFloat(2, 50, 200),
            'note' => fake()->optional()->sentence(),
        ], $overrides);

        return TaskTimeEntry::factory()->create($data);
    }

    /**
     * Generate multiple time entries.
     *
     * @param  int  $count  Number of entries to generate
     * @return array<TaskTimeEntry>
     */
    public static function generateTimeEntries(Task $task, User $user, int $count = 3): array
    {
        $entries = [];

        for ($i = 0; $i < $count; $i++) {
            $entries[] = self::generateTimeEntry($task, $user);
        }

        return $entries;
    }

    /**
     * Generate a billable time entry.
     */
    public static function generateBillableTimeEntry(Task $task, ?User $user = null): TaskTimeEntry
    {
        return self::generateTimeEntry($task, $user, [
            'is_billable' => true,
            'billing_rate' => fake()->randomFloat(2, 50, 200),
        ]);
    }

    /**
     * Generate a non-billable time entry.
     */
    public static function generateNonBillableTimeEntry(Task $task, ?User $user = null): TaskTimeEntry
    {
        return self::generateTimeEntry($task, $user, [
            'is_billable' => false,
            'billing_rate' => null,
        ]);
    }
}
