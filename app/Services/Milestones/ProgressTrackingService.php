<?php

declare(strict_types=1);

namespace App\Services\Milestones;

use App\Enums\MilestoneStatus;
use App\Models\Milestone;
use App\Models\MilestoneProgressSnapshot;
use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

final class ProgressTrackingService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {
    }

    public function calculateProgressFromTasks(Milestone $milestone): float
    {
        $tasks = $milestone->tasks()->withPivot('weight')->get();

        if ($tasks->isEmpty()) {
            return (float) $milestone->completion_percentage;
        }

        $totalWeight = (float) $tasks->sum(fn (Task $task): float => (float) ($task->pivot?->weight ?? 1));

        if ($totalWeight <= 0) {
            return 0.0;
        }

        $weightedProgress = (float) $tasks->sum(function (Task $task): float {
            $weight = (float) ($task->pivot?->weight ?? 1);
            $progress = $this->taskProgressPercentage($task);

            return $progress * $weight;
        });

        $progress = $weightedProgress / $totalWeight;

        return round(min(100.0, max(0.0, $progress)), 2);
    }

    public function calculateScheduleVarianceDays(Milestone $milestone, ?Carbon $today = null): int
    {
        $today ??= Date::today();

        $projectStart = $milestone->project?->start_date;
        $start = $projectStart instanceof Carbon ? $projectStart->copy()->startOfDay() : ($milestone->created_at?->copy()->startOfDay() ?? $today);

        $target = $milestone->target_date instanceof Carbon ? $milestone->target_date->copy()->startOfDay() : $today;

        $plannedDurationDays = max(1, $start->diffInDays($target));
        $ratePerDay = 100 / $plannedDurationDays;

        $elapsedDays = min($plannedDurationDays, max(0, $start->diffInDays($today)));
        $expectedProgress = min(100.0, round($elapsedDays * $ratePerDay, 2));

        $actualProgress = (float) $milestone->completion_percentage;
        $deltaProgress = $actualProgress - $expectedProgress;

        return (int) round($deltaProgress / $ratePerDay);
    }

    public function updateFromTasks(Milestone $milestone): void
    {
        $completion = $this->calculateProgressFromTasks($milestone);

        $tasks = $milestone->tasks()->get();
        $remainingTasksCount = $tasks->filter(fn (Task $task): bool => ! $task->isCompleted())->count();
        $blockedTasksCount = $tasks->filter(fn (Task $task): bool => $task->isBlocked())->count();

        $milestone->completion_percentage = $completion;
        $milestone->schedule_variance_days = $this->calculateScheduleVarianceDays($milestone);
        $milestone->is_at_risk = $milestone->schedule_variance_days <= (-1 * (int) config('milestones.risk.behind_schedule_days', 3));

        if (! $milestone->status->isTerminal() && $milestone->target_date->isPast() && $completion < 100) {
            $milestone->status = MilestoneStatus::OVERDUE;
        }

        $this->maybeNotifyProgressThresholds($milestone);

        $milestone->save();

        $milestone->progressSnapshots()->create([
            'completion_percentage' => $completion,
            'schedule_variance_days' => $milestone->schedule_variance_days,
            'remaining_tasks_count' => $remainingTasksCount,
            'blocked_tasks_count' => $blockedTasksCount,
        ]);
    }

    /**
     * @return array{trend: 'improving'|'stable'|'declining', latest: MilestoneProgressSnapshot|null}
     */
    public function generateTrendData(Milestone $milestone): array
    {
        $snapshots = $milestone->progressSnapshots()->take(2)->get();

        $latest = $snapshots->first();
        $previous = $snapshots->skip(1)->first();

        if (! $latest instanceof MilestoneProgressSnapshot || ! $previous instanceof MilestoneProgressSnapshot) {
            return [
                'trend' => 'stable',
                'latest' => $latest instanceof MilestoneProgressSnapshot ? $latest : null,
            ];
        }

        $delta = (float) $latest->completion_percentage - (float) $previous->completion_percentage;

        return [
            'trend' => $delta > 0 ? 'improving' : ($delta < 0 ? 'declining' : 'stable'),
            'latest' => $latest,
        ];
    }

    private function taskProgressPercentage(Task $task): float
    {
        try {
            return (float) $task->calculatePercentComplete();
        } catch (\Throwable) {
            return (float) ($task->percent_complete ?? 0);
        }
    }

    private function maybeNotifyProgressThresholds(Milestone $milestone): void
    {
        $thresholds = config('milestones.progress.thresholds', [25, 50, 75, 100]);

        $completion = (float) $milestone->completion_percentage;
        $lastNotified = (int) $milestone->last_progress_threshold_notified;

        foreach ($thresholds as $threshold) {
            $threshold = (int) $threshold;

            if ($threshold <= $lastNotified) {
                continue;
            }

            if ($completion < $threshold) {
                continue;
            }

            foreach ($milestone->notificationRecipients() as $user) {
                $this->notifications->sendActivityAlert(
                    $user,
                    __('notifications.milestones.progress_threshold_title', ['percent' => $threshold]),
                    __('notifications.milestones.progress_threshold_body', ['title' => $milestone->title, 'percent' => $threshold]),
                );
            }

            $lastNotified = $threshold;
        }

        $milestone->last_progress_threshold_notified = $lastNotified;
    }
}

