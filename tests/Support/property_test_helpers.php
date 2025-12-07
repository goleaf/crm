<?php

declare(strict_types=1);

use Tests\Support\Generators\ActivityGenerator;
use Tests\Support\Generators\NoteGenerator;
use Tests\Support\Generators\TaskGenerator;
use Tests\Support\Generators\TaskRelatedGenerator;

/**
 * Helper functions for property-based testing.
 *
 * These functions provide convenient access to generators and utilities
 * for creating test data in property-based tests.
 */
if (! function_exists('generateTask')) {
    /**
     * Generate a random task.
     *
     * @param  array<string, mixed>  $overrides
     */
    function generateTask(
        \App\Models\Team $team,
        ?\App\Models\User $creator = null,
        array $overrides = []
    ): \App\Models\Task {
        return TaskGenerator::generate($team, $creator, $overrides);
    }
}

if (! function_exists('generateNote')) {
    /**
     * Generate a random note.
     *
     * @param  array<string, mixed>  $overrides
     */
    function generateNote(
        \App\Models\Team $team,
        ?\App\Models\User $creator = null,
        array $overrides = []
    ): \App\Models\Note {
        return NoteGenerator::generate($team, $creator, $overrides);
    }
}

if (! function_exists('generateActivity')) {
    /**
     * Generate a random activity.
     *
     * @param  array<string, mixed>  $overrides
     */
    function generateActivity(
        \App\Models\Team $team,
        \Illuminate\Database\Eloquent\Model $subject,
        ?\App\Models\User $causer = null,
        array $overrides = []
    ): \App\Models\Activity {
        return ActivityGenerator::generate($team, $subject, $causer, $overrides);
    }
}

if (! function_exists('generateTaskReminder')) {
    /**
     * Generate a task reminder.
     *
     * @param  array<string, mixed>  $overrides
     */
    function generateTaskReminder(
        \App\Models\Task $task,
        ?\App\Models\User $user = null,
        array $overrides = []
    ): \App\Models\TaskReminder {
        return TaskRelatedGenerator::generateReminder($task, $user, $overrides);
    }
}

if (! function_exists('generateTaskRecurrence')) {
    /**
     * Generate a task recurrence pattern.
     *
     * @param  array<string, mixed>  $overrides
     */
    function generateTaskRecurrence(
        \App\Models\Task $task,
        array $overrides = []
    ): \App\Models\TaskRecurrence {
        return TaskRelatedGenerator::generateRecurrence($task, $overrides);
    }
}

if (! function_exists('generateTaskDelegation')) {
    /**
     * Generate a task delegation.
     *
     * @param  array<string, mixed>  $overrides
     */
    function generateTaskDelegation(
        \App\Models\Task $task,
        \App\Models\User $fromUser,
        \App\Models\User $toUser,
        array $overrides = []
    ): \App\Models\TaskDelegation {
        return TaskRelatedGenerator::generateDelegation($task, $fromUser, $toUser, $overrides);
    }
}

if (! function_exists('generateTaskChecklistItem')) {
    /**
     * Generate a task checklist item.
     *
     * @param  array<string, mixed>  $overrides
     */
    function generateTaskChecklistItem(
        \App\Models\Task $task,
        array $overrides = []
    ): \App\Models\TaskChecklistItem {
        return TaskRelatedGenerator::generateChecklistItem($task, $overrides);
    }
}

if (! function_exists('generateTaskComment')) {
    /**
     * Generate a task comment.
     *
     * @param  array<string, mixed>  $overrides
     */
    function generateTaskComment(
        \App\Models\Task $task,
        ?\App\Models\User $user = null,
        array $overrides = []
    ): \App\Models\TaskComment {
        return TaskRelatedGenerator::generateComment($task, $user, $overrides);
    }
}

if (! function_exists('generateTaskTimeEntry')) {
    /**
     * Generate a task time entry.
     *
     * @param  array<string, mixed>  $overrides
     */
    function generateTaskTimeEntry(
        \App\Models\Task $task,
        ?\App\Models\User $user = null,
        array $overrides = []
    ): \App\Models\TaskTimeEntry {
        return TaskRelatedGenerator::generateTimeEntry($task, $user, $overrides);
    }
}

if (! function_exists('runPropertyTest')) {
    /**
     * Run a property test with the specified number of iterations.
     *
     * @param  callable  $test  The test function to run
     * @param  int  $iterations  Number of iterations (default: 100)
     */
    function runPropertyTest(callable $test, int $iterations = 100): void
    {
        for ($i = 0; $i < $iterations; $i++) {
            $test($i);
        }
    }
}

if (! function_exists('randomSubset')) {
    /**
     * Generate a random subset of an array.
     *
     * @template T
     *
     * @param  array<T>  $items
     * @return array<T>
     */
    function randomSubset(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        $count = fake()->numberBetween(0, count($items));

        if ($count === 0) {
            return [];
        }

        return fake()->randomElements($items, $count);
    }
}

if (! function_exists('randomDate')) {
    /**
     * Generate a random date within a range.
     */
    function randomDate(?string $startDate = '-1 year', ?string $endDate = '+1 year'): \Illuminate\Support\Carbon
    {
        return \Illuminate\Support\Carbon::parse(
            fake()->dateTimeBetween($startDate, $endDate)
        );
    }
}

if (! function_exists('randomBoolean')) {
    /**
     * Generate a random boolean with optional bias.
     *
     * @param  float  $trueProbability  Probability of returning true (0.0 to 1.0)
     */
    function randomBoolean(float $trueProbability = 0.5): bool
    {
        return fake()->boolean((int) ($trueProbability * 100));
    }
}
