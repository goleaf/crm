<?php

declare(strict_types=1);

namespace App\Services\Task;

use App\Models\Task;
use App\Models\TaskDelegation;
use App\Models\User;
use Illuminate\Support\Collection;

final class TaskDelegationService
{
    /**
     * Delegate a task from one user to another.
     */
    public function delegateTask(Task $task, User $from, User $to, ?string $note = null): TaskDelegation
    {
        $delegation = TaskDelegation::create([
            'task_id' => $task->id,
            'from_user_id' => $from->id,
            'to_user_id' => $to->id,
            'status' => 'pending',
            'delegated_at' => now(),
            'note' => $note,
        ]);

        // Add the delegatee as an assignee if not already assigned
        if (! $task->assignees->contains($to->id)) {
            $task->assignees()->attach($to->id);
        }

        // Send notification to delegatee
        $this->notifyDelegation($delegation);

        return $delegation;
    }

    /**
     * Accept a delegated task.
     */
    public function acceptDelegation(TaskDelegation $delegation): bool
    {
        if ($delegation->status !== 'pending') {
            return false;
        }

        return $delegation->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    /**
     * Decline a delegated task.
     */
    public function declineDelegation(TaskDelegation $delegation, string $reason): bool
    {
        if ($delegation->status !== 'pending') {
            return false;
        }

        $updated = $delegation->update([
            'status' => 'declined',
            'declined_at' => now(),
            'note' => $delegation->note . "\n\nDeclined: " . $reason,
        ]);

        if ($updated) {
            // Remove the delegatee from assignees
            $delegation->task->assignees()->detach($delegation->to_user_id);
        }

        return $updated;
    }

    /**
     * Send delegation notification.
     */
    public function notifyDelegation(TaskDelegation $delegation): void
    {
        // TODO: Implement notification sending logic
        // This will be implemented when notification classes are created
    }

    /**
     * Get delegation history for a task.
     *
     * @return Collection<int, TaskDelegation>
     */
    public function getDelegationHistory(Task $task): Collection
    {
        return TaskDelegation::query()
            ->where('task_id', $task->id)
            ->with(['from', 'to'])
            ->orderBy('delegated_at', 'desc')
            ->get();
    }

    /**
     * Get pending delegations for a user.
     *
     * @return Collection<int, TaskDelegation>
     */
    public function getPendingDelegations(User $user): Collection
    {
        return TaskDelegation::query()
            ->where('to_user_id', $user->id)
            ->where('status', 'pending')
            ->with(['task', 'from'])
            ->orderBy('delegated_at', 'desc')
            ->get();
    }

    /**
     * Get delegations made by a user.
     *
     * @return Collection<int, TaskDelegation>
     */
    public function getDelegationsMadeBy(User $user): Collection
    {
        return TaskDelegation::query()
            ->where('from_user_id', $user->id)
            ->with(['task', 'to'])
            ->orderBy('delegated_at', 'desc')
            ->get();
    }

    /**
     * Check if a task has pending delegations.
     */
    public function hasPendingDelegations(Task $task): bool
    {
        return TaskDelegation::query()
            ->where('task_id', $task->id)
            ->where('status', 'pending')
            ->exists();
    }
}

