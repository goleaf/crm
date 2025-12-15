<?php

declare(strict_types=1);

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;

    actingAs($this->user);
});

/**
 * **Feature: technical-debt-todos, Property 1: Eager Loading Prevents N+1 Queries**
 * **Validates: Requirements 1.1, 1.3**
 *
 * Property: Query count does not increase linearly when accessing relationships across collections.
 */
test('property: automatic eager loading batches relationship queries', function (): void {
    runPropertyTest(function (): void {
        DB::beginTransaction();

        try {
            $taskCount = fake()->numberBetween(2, 8);
            $assignee = User::factory()->create();
            $assignee->teams()->attach($this->team);

            /** @var array<int, int> $taskIds */
            $taskIds = Task::factory()
                ->count($taskCount)
                ->create([
                    'team_id' => $this->team->id,
                    'creator_id' => $this->user->id,
                ])
                ->each(function (Task $task) use ($assignee): void {
                    $task->assignees()->attach($assignee->id);
                })
                ->pluck('id')
                ->all();

            DB::flushQueryLog();
            DB::enableQueryLog();

            $many = Task::query()->whereKey($taskIds)->get();
            $many->each(fn (Task $task): int => $task->assignees->count());
            $manyQueryCount = count(DB::getQueryLog());

            DB::flushQueryLog();
            DB::enableQueryLog();

            $one = Task::query()->whereKey([$taskIds[0]])->get();
            $one->firstOrFail()->assignees->count();
            $oneQueryCount = count(DB::getQueryLog());

            expect($manyQueryCount)->toBeLessThanOrEqual($oneQueryCount + 1);
        } finally {
            DB::rollBack();
        }
    }, 100);
})->group('property');

/**
 * **Feature: technical-debt-todos, Property 2: Strict Mode Prevents Lazy Loading**
 * **Validates: Requirements 2.2**
 *
 * Property: When automatic eager loading is disabled, strict mode throws on lazy loading across collections.
 */
test('property: strict mode throws on lazy loading', function (): void {
    $originalAutoEagerLoading = Model::isAutomaticallyEagerLoadingRelationships();

    Model::automaticallyEagerLoadRelationships(false);

    try {
        runPropertyTest(function (): void {
            DB::beginTransaction();

            try {
                $users = User::factory()->count(2)->create();

                /** @var \Illuminate\Database\Eloquent\Collection<int, User> $fetched */
                $fetched = User::query()->whereKey($users->pluck('id')->all())->get();

                expect(fn () => $fetched->firstOrFail()->tasks)->toThrow(LazyLoadingViolationException::class);
            } finally {
                DB::rollBack();
            }
        }, 100);
    } finally {
        Model::automaticallyEagerLoadRelationships($originalAutoEagerLoading);
    }
})->group('property');

/**
 * **Feature: technical-debt-todos, Property 3: Strict Mode Prevents Mass Assignment Violations**
 * **Validates: Requirements 2.3**
 *
 * Property: When strict mode is enabled, passing non-fillable attributes throws a MassAssignmentException.
 */
test('property: strict mode throws on mass assignment violations', function (): void {
    runPropertyTest(function (): void {
        expect(fn () => User::create([
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'definitely_not_fillable' => fake()->word(),
        ]))->toThrow(MassAssignmentException::class);
    }, 100);
})->group('property');

/**
 * **Feature: technical-debt-todos, Property 4: Strict Mode Prevents Missing Attribute Access**
 * **Validates: Requirements 2.4**
 *
 * Property: When strict mode is enabled, accessing a missing attribute throws a MissingAttributeException.
 */
test('property: strict mode throws on missing attribute access', function (): void {
    runPropertyTest(function (): void {
        $user = User::factory()->create()->fresh();

        expect(fn () => $user?->definitely_missing_attribute)->toThrow(MissingAttributeException::class);
    }, 100);
})->group('property');

/**
 * **Feature: technical-debt-todos, Property 5: New Assignees Receive Notifications**
 * **Validates: Requirements 3.1, 3.6**
 *
 * Property: Newly assigned users receive exactly one notification and are marked as notified.
 */
test('property: newly assigned users receive a task assignment notification', function (): void {
    runPropertyTest(function (): void {
        DB::beginTransaction();

        try {
            $task = Task::factory()->create([
                'team_id' => $this->team->id,
                'creator_id' => $this->user->id,
            ]);

            $assigneeCount = fake()->numberBetween(1, 3);
            $assignees = User::factory()->count($assigneeCount)->create();

            foreach ($assignees as $assignee) {
                $assignee->teams()->attach($this->team);
                $task->assignees()->attach($assignee->id);
            }

            TaskResource::notifyNewAssignees($task);

            foreach ($assignees as $assignee) {
                expect($assignee->notifications()
                    ->where('data->viewData->task_id', $task->getKey())
                    ->count())
                    ->toBe(1);

                expect(DB::table('task_user')
                    ->where('task_id', $task->getKey())
                    ->where('user_id', $assignee->getKey())
                    ->value('notified_at'))
                    ->not
                    ->toBeNull();
            }
        } finally {
            DB::rollBack();
        }
    }, 100);
})->group('property');

/**
 * **Feature: technical-debt-todos, Property 6: Existing Assignees Don't Receive Duplicate Notifications**
 * **Validates: Requirements 3.2, 3.4**
 *
 * Property: Notifying an unchanged assignee list does not create duplicate notifications.
 */
test('property: existing assignees do not receive duplicate notifications', function (): void {
    runPropertyTest(function (): void {
        DB::beginTransaction();

        try {
            $task = Task::factory()->create([
                'team_id' => $this->team->id,
                'creator_id' => $this->user->id,
            ]);

            $assignee = User::factory()->create();
            $assignee->teams()->attach($this->team);
            $task->assignees()->attach($assignee->id);

            TaskResource::notifyNewAssignees($task);
            TaskResource::notifyNewAssignees($task);

            expect($assignee->notifications()
                ->where('data->viewData->task_id', $task->getKey())
                ->count())
                ->toBe(1);
        } finally {
            DB::rollBack();
        }
    }, 100);
})->group('property');

/**
 * **Feature: technical-debt-todos, Property 7: Only New Assignees Receive Notifications on Update**
 * **Validates: Requirements 3.3**
 *
 * Property: Adding assignees only notifies the newly attached users, not existing assignees.
 */
test('property: only newly added assignees are notified on update', function (): void {
    runPropertyTest(function (): void {
        DB::beginTransaction();

        try {
            $task = Task::factory()->create([
                'team_id' => $this->team->id,
                'creator_id' => $this->user->id,
            ]);

            $existing = User::factory()->create();
            $existing->teams()->attach($this->team);
            $task->assignees()->attach($existing->id);

            TaskResource::notifyNewAssignees($task);

            $new = User::factory()->create();
            $new->teams()->attach($this->team);
            $task->assignees()->attach($new->id);

            TaskResource::notifyNewAssignees($task);

            expect($existing->notifications()
                ->where('data->viewData->task_id', $task->getKey())
                ->count())
                ->toBe(1);

            expect($new->notifications()
                ->where('data->viewData->task_id', $task->getKey())
                ->count())
                ->toBe(1);
        } finally {
            DB::rollBack();
        }
    }, 100);
})->group('property');
