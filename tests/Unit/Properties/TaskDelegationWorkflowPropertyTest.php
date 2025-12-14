<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use App\Services\Task\TaskDelegationService;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->team = Team::factory()->create();
    $this->delegator = User::factory()->create();
    $this->delegatee = User::factory()->create();

    $this->delegator->teams()->attach($this->team);
    $this->delegatee->teams()->attach($this->team);

    actingAs($this->delegator);

    $this->delegationService = resolve(TaskDelegationService::class);
});

/**
 * **Feature: tasks-activities-enhancement, Property 25: Task delegation workflow**
 * **Validates: Requirements 18.1, 18.2, 18.4**
 *
 * Property: Delegating a task creates a delegation record, adds the delegatee as an assignee,
 * and acceptance/decline transitions update state appropriately.
 */
test('property: delegation lifecycle updates assignment and status', function (): void {
    runPropertyTest(function (): void {
        $task = generateTask($this->team, $this->delegator);

        $delegation = $this->delegationService->delegateTask(
            $task,
            $this->delegator,
            $this->delegatee,
            note: fake()->optional()->sentence(),
        );

        expect($delegation->status)->toBe('pending');
        expect($task->fresh()->assignees->contains($this->delegatee->id))->toBeTrue(
            'Delegatee should be added as assignee on delegation',
        );

        // Accept the delegation
        $accepted = $this->delegationService->acceptDelegation($delegation);
        expect($accepted)->toBeTrue();
        expect($delegation->fresh()->status)->toBe('accepted');

        // Decline a new delegation should remove assignee
        $task2 = generateTask($this->team, $this->delegator);
        $delegation2 = $this->delegationService->delegateTask($task2, $this->delegator, $this->delegatee);

        $declined = $this->delegationService->declineDelegation($delegation2, 'No capacity');
        expect($declined)->toBeTrue();
        expect($delegation2->fresh()->status)->toBe('declined');
        expect($task2->fresh()->assignees->contains($this->delegatee->id))->toBeFalse(
            'Delegatee should be detached when delegation is declined',
        );
    }, 25);
})->group('property');
