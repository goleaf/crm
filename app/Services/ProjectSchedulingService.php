<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Collection;

/**
 * Service for project scheduling operations including critical path calculation,
 * timeline generation, and schedule analysis.
 */
final class ProjectSchedulingService
{
    /**
     * Calculate the critical path for a project.
     * Returns an ordered collection of tasks that form the longest path through the project.
     *
     * @return Collection<int, Task>
     */
    public function calculateCriticalPath(Project $project): Collection
    {
        $tasks = $project->tasks()
            ->with(['dependencies', 'dependents'])
            ->get();

        if ($tasks->isEmpty()) {
            return collect();
        }

        // Calculate earliest start and finish times (forward pass)
        $earliestTimes = $this->calculateEarliestTimes($tasks);

        // Calculate latest start and finish times (backward pass)
        $latestTimes = $this->calculateLatestTimes($tasks, $earliestTimes);

        // Identify critical tasks (those with zero slack)
        $criticalTasks = $tasks->filter(function (Task $task) use ($earliestTimes, $latestTimes): bool {
            $taskId = $task->id;
            $slack = $latestTimes[$taskId]['start'] - $earliestTimes[$taskId]['start'];

            return $slack === 0;
        });

        // Order critical tasks by earliest start time
        return $criticalTasks->sortBy(fn (Task $task) => $earliestTimes[$task->id]['start']);
    }

    /**
     * Calculate earliest start and finish times for all tasks (forward pass).
     *
     * @param Collection<int, Task> $tasks
     *
     * @return array<int, array{start: int, finish: int}>
     */
    private function calculateEarliestTimes(Collection $tasks): array
    {
        $times = [];
        $processed = [];

        // Initialize all tasks
        foreach ($tasks as $task) {
            $times[$task->id] = ['start' => 0, 'finish' => 0];
        }

        // Process tasks in dependency order
        $toProcess = $tasks->pluck('id')->toArray();

        while (! empty($toProcess)) {
            $madeProgress = false;

            foreach ($toProcess as $index => $taskId) {
                $task = $tasks->firstWhere('id', $taskId);

                if ($task === null) {
                    unset($toProcess[$index]);

                    continue;
                }

                // Check if all dependencies are processed
                $dependencies = $task->dependencies;
                $allDependenciesProcessed = $dependencies->every(
                    fn (Task $dep): bool => in_array($dep->id, $processed, true),
                );

                if (! $allDependenciesProcessed && $dependencies->isNotEmpty()) {
                    continue;
                }

                // Calculate earliest start time
                $earliestStart = 0;
                foreach ($dependencies as $dependency) {
                    $depFinish = $times[$dependency->id]['finish'];
                    $earliestStart = max($earliestStart, $depFinish);
                }

                // Calculate duration in days
                $duration = $this->getTaskDurationInDays($task);

                $times[$task->id] = [
                    'start' => $earliestStart,
                    'finish' => $earliestStart + $duration,
                ];

                $processed[] = $task->id;
                unset($toProcess[$index]);
                $madeProgress = true;
            }

            // Prevent infinite loop if there are circular dependencies
            if (! $madeProgress && ! empty($toProcess)) {
                break;
            }
        }

        return $times;
    }

    /**
     * Calculate latest start and finish times for all tasks (backward pass).
     *
     * @param Collection<int, Task>                      $tasks
     * @param array<int, array{start: int, finish: int}> $earliestTimes
     *
     * @return array<int, array{start: int, finish: int}>
     */
    private function calculateLatestTimes(Collection $tasks, array $earliestTimes): array
    {
        $times = [];

        // Find project completion time (maximum earliest finish)
        $projectCompletion = max(array_column($earliestTimes, 'finish'));

        // Initialize all tasks with project completion time
        foreach ($tasks as $task) {
            $times[$task->id] = [
                'start' => $projectCompletion,
                'finish' => $projectCompletion,
            ];
        }

        // Process tasks in reverse dependency order
        $processed = [];
        $toProcess = $tasks->pluck('id')->toArray();

        while (! empty($toProcess)) {
            $madeProgress = false;

            foreach ($toProcess as $index => $taskId) {
                $task = $tasks->firstWhere('id', $taskId);

                if ($task === null) {
                    unset($toProcess[$index]);

                    continue;
                }

                // Check if all dependents are processed
                $dependents = $task->dependents;
                $allDependentsProcessed = $dependents->every(
                    fn (Task $dep): bool => in_array($dep->id, $processed, true),
                );

                if (! $allDependentsProcessed && $dependents->isNotEmpty()) {
                    continue;
                }

                // Calculate latest finish time
                $latestFinish = $projectCompletion;
                if ($dependents->isNotEmpty()) {
                    $latestFinish = $dependents->min(fn (Task $dependent): float|int => $times[$dependent->id]['start']);
                }

                // Calculate duration in days
                $duration = $this->getTaskDurationInDays($task);

                $times[$task->id] = [
                    'start' => $latestFinish - $duration,
                    'finish' => $latestFinish,
                ];

                $processed[] = $task->id;
                unset($toProcess[$index]);
                $madeProgress = true;
            }

            // Prevent infinite loop
            if (! $madeProgress && ! empty($toProcess)) {
                break;
            }
        }

        return $times;
    }

    /**
     * Get task duration in days.
     */
    private function getTaskDurationInDays(Task $task): int
    {
        // If task has start and end dates, calculate actual duration
        if ($task->start_date !== null && $task->end_date !== null) {
            return max(1, $task->start_date->diffInDays($task->end_date));
        }

        // If task has estimated duration in minutes, convert to days (8 hours per day)
        if ($task->estimated_duration_minutes !== null && $task->estimated_duration_minutes > 0) {
            return max(1, (int) ceil($task->estimated_duration_minutes / (8 * 60)));
        }

        // Default to 1 day
        return 1;
    }

    /**
     * Calculate slack time for a task.
     */
    public function calculateSlack(Task $task, Project $project): int
    {
        $tasks = $project->tasks()->with(['dependencies', 'dependents'])->get();
        $earliestTimes = $this->calculateEarliestTimes($tasks);
        $latestTimes = $this->calculateLatestTimes($tasks, $earliestTimes);

        if (! isset($earliestTimes[$task->id]) || ! isset($latestTimes[$task->id])) {
            return 0;
        }

        return $latestTimes[$task->id]['start'] - $earliestTimes[$task->id]['start'];
    }

    /**
     * Generate a timeline for the project with all tasks scheduled.
     *
     * @return array<string, mixed>
     */
    public function generateTimeline(Project $project): array
    {
        $tasks = $project->tasks()
            ->with(['dependencies', 'assignees'])
            ->get();

        if ($tasks->isEmpty()) {
            return [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'start_date' => $project->start_date?->format('Y-m-d'),
                'end_date' => $project->end_date?->format('Y-m-d'),
                'tasks' => [],
                'milestones' => [],
            ];
        }

        $earliestTimes = $this->calculateEarliestTimes($tasks);
        $latestTimes = $this->calculateLatestTimes($tasks, $earliestTimes);

        // Calculate project start date
        $projectStart = $project->start_date ?? \Illuminate\Support\Facades\Date::today();

        $taskTimeline = $tasks->map(function (Task $task) use ($earliestTimes, $latestTimes, $projectStart): array {
            $earliestStart = $earliestTimes[$task->id]['start'];
            $earliestFinish = $earliestTimes[$task->id]['finish'];
            $latestStart = $latestTimes[$task->id]['start'];
            $slack = $latestStart - $earliestStart;

            $scheduledStart = $projectStart->copy()->addDays($earliestStart);
            $scheduledEnd = $projectStart->copy()->addDays($earliestFinish);

            return [
                'task_id' => $task->id,
                'task_name' => $task->title,
                'is_milestone' => $task->is_milestone,
                'scheduled_start' => $scheduledStart->format('Y-m-d'),
                'scheduled_end' => $scheduledEnd->format('Y-m-d'),
                'duration_days' => $earliestFinish - $earliestStart,
                'slack_days' => $slack,
                'is_critical' => $slack === 0,
                'percent_complete' => (float) $task->percent_complete,
                'assignees' => $task->assignees->pluck('name')->toArray(),
                'dependencies' => $task->dependencies->pluck('id')->toArray(),
            ];
        })->all();

        // Extract milestones
        $milestones = collect($taskTimeline)
            ->filter(fn (array $task) => $task['is_milestone'])
            ->values()
            ->all();

        return [
            'project_id' => $project->id,
            'project_name' => $project->name,
            'start_date' => $projectStart->format('Y-m-d'),
            'end_date' => $projectStart->copy()->addDays(max(array_column($earliestTimes, 'finish')))->format('Y-m-d'),
            'tasks' => $taskTimeline,
            'milestones' => $milestones,
        ];
    }

    /**
     * Get project schedule summary with key metrics.
     *
     * @return array<string, mixed>
     */
    public function getScheduleSummary(Project $project): array
    {
        $tasks = $project->tasks()->with(['dependencies', 'dependents'])->get();

        if ($tasks->isEmpty()) {
            return [
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'in_progress_tasks' => 0,
                'blocked_tasks' => 0,
                'critical_path_length' => 0,
                'critical_tasks_count' => 0,
                'on_schedule' => true,
            ];
        }

        $criticalPath = $this->calculateCriticalPath($project);
        $earliestTimes = $this->calculateEarliestTimes($tasks);

        $completedTasks = $tasks->filter(fn (Task $task): bool => $task->isCompleted())->count();
        $inProgressTasks = $tasks->filter(fn (Task $task): bool => ! $task->isCompleted() && (float) $task->percent_complete > 0)->count();
        $blockedTasks = $tasks->filter(fn (Task $task): bool => $task->isBlocked())->count();

        $criticalPathLength = $criticalPath->isNotEmpty()
            ? max(array_column($earliestTimes, 'finish'))
            : 0;

        // Check if project is on schedule
        $onSchedule = true;
        if ($project->end_date !== null) {
            $projectStart = $project->start_date ?? \Illuminate\Support\Facades\Date::today();
            $estimatedEnd = $projectStart->copy()->addDays($criticalPathLength);
            $onSchedule = $estimatedEnd->lessThanOrEqualTo($project->end_date);
        }

        return [
            'total_tasks' => $tasks->count(),
            'completed_tasks' => $completedTasks,
            'in_progress_tasks' => $inProgressTasks,
            'blocked_tasks' => $blockedTasks,
            'critical_path_length' => $criticalPathLength,
            'critical_tasks_count' => $criticalPath->count(),
            'on_schedule' => $onSchedule,
        ];
    }
}
