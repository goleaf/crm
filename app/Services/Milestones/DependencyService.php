<?php

declare(strict_types=1);

namespace App\Services\Milestones;

use App\Enums\DependencyType;
use App\Enums\MilestoneStatus;
use App\Models\Milestone;
use App\Models\MilestoneDependency;
use App\Services\NotificationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

final class DependencyService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {
    }

    public function createDependency(
        Milestone $predecessor,
        Milestone $successor,
        DependencyType $type,
        int $lagDays = 0,
    ): MilestoneDependency {
        if ($predecessor->is($successor)) {
            throw new \DomainException(__('app.messages.circular_dependency_detected'));
        }

        if ($this->wouldCreateCycle($predecessor, $successor)) {
            throw new \DomainException(__('app.messages.circular_dependency_detected'));
        }

        return MilestoneDependency::query()->create([
            'predecessor_id' => $predecessor->getKey(),
            'successor_id' => $successor->getKey(),
            'dependency_type' => $type,
            'lag_days' => $lagDays,
            'is_active' => true,
        ]);
    }

    public function canStart(Milestone $milestone, ?Carbon $today = null): bool
    {
        $today ??= Date::today();

        $dependencies = $milestone->dependencies()
            ->where('is_active', true)
            ->with('predecessor')
            ->get();

        foreach ($dependencies as $dependency) {
            $predecessor = $dependency->predecessor;

            if (! $predecessor instanceof Milestone) {
                return false;
            }

            if (! $this->dependencySatisfied($dependency->dependency_type, $predecessor->status)) {
                return false;
            }

            if ($dependency->dependency_type === DependencyType::FINISH_TO_START && $dependency->lag_days > 0) {
                $base = $predecessor->actual_completion_date ?? $predecessor->target_date;

                if ($base->copy()->addDays($dependency->lag_days)->isFuture()) {
                    return false;
                }
            }
        }

        return true;
    }

    public function cascadeTargetDateChange(Milestone $predecessor, Carbon $oldTargetDate, Carbon $newTargetDate): void
    {
        $deltaDays = $newTargetDate->diffInDays($oldTargetDate, false);

        if ($deltaDays <= 0) {
            return;
        }

        $this->cascadeShift($predecessor, $deltaDays, visited: []);
    }

    private function cascadeShift(Milestone $predecessor, int $deltaDays, array $visited): void
    {
        $visited[$predecessor->getKey()] = true;

        $links = MilestoneDependency::query()
            ->where('predecessor_id', $predecessor->getKey())
            ->where('is_active', true)
            ->with('successor')
            ->get();

        foreach ($links as $link) {
            $successor = $link->successor;

            if (! $successor instanceof Milestone) {
                continue;
            }

            if (isset($visited[$successor->getKey()])) {
                continue;
            }

            $successor->target_date = $successor->target_date->copy()->addDays($deltaDays);
            $successor->save();

            if ($successor->owner !== null) {
                $this->notifications->sendActivityAlert(
                    $successor->owner,
                    __('notifications.milestones.dependency_shift_title'),
                    __('notifications.milestones.dependency_shift_body', [
                        'title' => $successor->title,
                        'days' => $deltaDays,
                    ]),
                );
            }

            $this->cascadeShift($successor, $deltaDays, $visited);
        }
    }

    private function wouldCreateCycle(Milestone $predecessor, Milestone $successor): bool
    {
        $targetId = (int) $predecessor->getKey();
        $stack = [(int) $successor->getKey()];
        $visited = [];

        while ($stack !== []) {
            $currentId = array_pop($stack);

            if (isset($visited[$currentId])) {
                continue;
            }

            $visited[$currentId] = true;

            if ($currentId === $targetId) {
                return true;
            }

            $nextIds = MilestoneDependency::query()
                ->where('predecessor_id', $currentId)
                ->where('is_active', true)
                ->pluck('successor_id')
                ->all();

            foreach ($nextIds as $nextId) {
                $stack[] = (int) $nextId;
            }
        }

        return false;
    }

    private function dependencySatisfied(DependencyType $type, MilestoneStatus $predecessorStatus): bool
    {
        return match ($type) {
            DependencyType::FINISH_TO_START, DependencyType::FINISH_TO_FINISH => $predecessorStatus === MilestoneStatus::COMPLETED,
            DependencyType::START_TO_START, DependencyType::START_TO_FINISH => in_array($predecessorStatus, [
                MilestoneStatus::IN_PROGRESS,
                MilestoneStatus::READY_FOR_REVIEW,
                MilestoneStatus::UNDER_REVIEW,
                MilestoneStatus::COMPLETED,
            ], true),
        };
    }
}

