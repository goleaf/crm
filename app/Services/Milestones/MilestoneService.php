<?php

declare(strict_types=1);

namespace App\Services\Milestones;

use App\Enums\MilestoneApprovalStatus;
use App\Enums\MilestoneStatus;
use App\Models\Deliverable;
use App\Models\Milestone;
use App\Models\MilestoneApproval;
use App\Models\MilestoneTemplate;
use App\Models\Project;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;

final class MilestoneService
{
    public function __construct(
        private readonly DependencyService $dependencies,
        private readonly ProgressTrackingService $progressTracking,
        private readonly NotificationService $notifications,
    ) {
    }

    /**
     * @param array{
     *   title: string,
     *   description?: string|null,
     *   target_date: \DateTimeInterface|string,
     *   project_id: int,
     *   milestone_type: string|\BackedEnum,
     *   priority_level: string|\BackedEnum,
     *   owner_id: int,
     *   is_critical?: bool,
     *   requires_approval?: bool,
     *   stakeholder_ids?: array<int, int>,
     *   reference_links?: array<int, array<string, mixed>>,
     * } $data
     */
    public function createMilestone(array $data): Milestone
    {
        /** @var Project $project */
        $project = Project::query()->findOrFail($data['project_id']);

        /** @var User $owner */
        $owner = User::query()->findOrFail($data['owner_id']);

        $this->assertOwnerHasProjectAccess($project, $owner);

        $targetDate = $data['target_date'] instanceof \DateTimeInterface
            ? Date::instance($data['target_date'])
            : Date::parse($data['target_date']);

        return DB::transaction(function () use ($data, $project, $owner, $targetDate): Milestone {
            $milestone = Milestone::query()->create([
                'team_id' => $project->team_id,
                'project_id' => $project->getKey(),
                'owner_id' => $owner->getKey(),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'target_date' => $targetDate->toDateString(),
                'milestone_type' => $data['milestone_type'],
                'priority_level' => $data['priority_level'],
                'status' => MilestoneStatus::NOT_STARTED,
                'completion_percentage' => 0,
                'schedule_variance_days' => 0,
                'is_critical' => (bool) ($data['is_critical'] ?? false),
                'is_at_risk' => false,
                'stakeholder_ids' => $data['stakeholder_ids'] ?? null,
                'reference_links' => $data['reference_links'] ?? null,
                'requires_approval' => (bool) ($data['requires_approval'] ?? false),
            ]);

            $this->notifyAssignment($milestone);

            return $milestone;
        });
    }

    /**
     * @return array{within_timeline: bool, warnings: array<int, string>}
     */
    public function validateTargetDate(Project $project, Carbon $targetDate): array
    {
        $warnings = [];

        if ($project->start_date instanceof Carbon && $targetDate->lessThan($project->start_date)) {
            $warnings[] = __('app.messages.milestone_target_date_outside_project_timeline');
        }

        if ($project->end_date instanceof Carbon && $targetDate->greaterThan($project->end_date)) {
            $warnings[] = __('app.messages.milestone_target_date_outside_project_timeline');
        }

        return [
            'within_timeline' => $warnings === [],
            'warnings' => $warnings,
        ];
    }

    public function updateProgress(Milestone $milestone): void
    {
        $this->progressTracking->updateFromTasks($milestone);
    }

    public function updateStatus(Milestone $milestone, MilestoneStatus $status): void
    {
        if ($status === MilestoneStatus::IN_PROGRESS && ! $this->dependencies->canStart($milestone)) {
            throw new \DomainException(__('app.messages.milestone_dependency_not_satisfied'));
        }

        $oldStatus = $milestone->status;
        $milestone->status = $status;
        $milestone->save();

        foreach ($milestone->notificationRecipients() as $user) {
            $this->notifications->sendActivityAlert(
                $user,
                __('notifications.milestones.status_changed_title'),
                __('notifications.milestones.status_changed_body', [
                    'title' => $milestone->title,
                    'from' => $oldStatus->getLabel(),
                    'to' => $status->getLabel(),
                ]),
            );
        }
    }

    public function syncStatusFromDeliverables(Milestone $milestone): void
    {
        $deliverables = $milestone->deliverables()->get();

        if ($deliverables->isEmpty()) {
            return;
        }

        $allCompleted = $deliverables->every(fn (Deliverable $deliverable): bool => $deliverable->status === \App\Enums\DeliverableStatus::COMPLETED);

        if (! $allCompleted) {
            return;
        }

        if ($milestone->status === MilestoneStatus::COMPLETED || $milestone->status === MilestoneStatus::CANCELLED) {
            return;
        }

        $milestone->status = MilestoneStatus::READY_FOR_REVIEW;
        $milestone->save();

        if ($milestone->owner !== null) {
            $this->notifications->sendActivityAlert(
                $milestone->owner,
                __('notifications.milestones.ready_for_review_title'),
                __('notifications.milestones.ready_for_review_body', ['title' => $milestone->title]),
            );
        }
    }

    /**
     * @param array<int, array{approver_id: int, approval_criteria?: string|null}> $steps
     */
    public function submitForApproval(Milestone $milestone, array $steps): void
    {
        if ($steps === []) {
            throw new \InvalidArgumentException('Approval steps are required.');
        }

        DB::transaction(function () use ($milestone, $steps): void {
            $milestone->approvals()->delete();

            foreach ($steps as $index => $step) {
                $milestone->approvals()->create([
                    'step_order' => $index + 1,
                    'approver_id' => $step['approver_id'],
                    'approval_criteria' => $step['approval_criteria'] ?? null,
                    'status' => MilestoneApprovalStatus::PENDING,
                    'requested_at' => now(),
                ]);
            }

            $milestone->submitted_for_approval_at = now();
            $milestone->status = MilestoneStatus::UNDER_REVIEW;
            $milestone->requires_approval = true;
            $milestone->save();

            $milestone->approvals()->with('approver')->get()->each(function (MilestoneApproval $approval) use ($milestone): void {
                if ($approval->approver !== null) {
                    $this->notifications->sendActivityAlert(
                        $approval->approver,
                        __('notifications.milestones.approval_requested_title'),
                        __('notifications.milestones.approval_requested_body', ['title' => $milestone->title]),
                    );
                }
            });
        });
    }

    public function recordApprovalDecision(MilestoneApproval $approval, MilestoneApprovalStatus $decision, ?string $comment = null): void
    {
        DB::transaction(function () use ($approval, $decision, $comment): void {
            $approval->status = $decision;
            $approval->decision_comment = $comment;
            $approval->decided_at = now();
            $approval->save();

            $milestone = $approval->milestone;
            $milestone->load('approvals');

            if ($decision === MilestoneApprovalStatus::REJECTED) {
                $milestone->status = MilestoneStatus::IN_PROGRESS;
                $milestone->save();

                if ($milestone->owner !== null) {
                    $this->notifications->sendActivityAlert(
                        $milestone->owner,
                        __('notifications.milestones.approval_rejected_title'),
                        __('notifications.milestones.approval_rejected_body', [
                            'title' => $milestone->title,
                            'comment' => $comment ?? '',
                        ]),
                    );
                }

                return;
            }

            $allApproved = $milestone->approvals->isNotEmpty()
                && $milestone->approvals->every(fn (MilestoneApproval $a): bool => $a->status === MilestoneApprovalStatus::APPROVED);

            if (! $allApproved) {
                return;
            }

            $milestone->status = MilestoneStatus::COMPLETED;
            $milestone->actual_completion_date = Date::today()->toDateString();
            $milestone->completion_percentage = 100;
            $milestone->save();

            foreach ($milestone->notificationRecipients() as $user) {
                $this->notifications->sendActivityAlert(
                    $user,
                    __('notifications.milestones.completed_title'),
                    __('notifications.milestones.completed_body', ['title' => $milestone->title]),
                );
            }
        });
    }

    /**
     * @param array{
     *   base_date?: \DateTimeInterface|string|null,
     *   milestones?: array<int, array{title?: string, target_date?: \DateTimeInterface|string|null, owner_id?: int|null}>,
     * } $overrides
     *
     * @return Collection<int, Milestone>
     */
    public function applyTemplate(MilestoneTemplate $template, Project $project, array $overrides = []): Collection
    {
        $data = $template->template_data;
        $definitions = $data['milestones'] ?? [];

        if (! is_array($definitions) || $definitions === []) {
            return collect();
        }

        $baseDate = $overrides['base_date'] ?? $project->start_date ?? Date::today();
        $baseDate = $baseDate instanceof \DateTimeInterface ? Date::instance($baseDate) : Date::parse((string) $baseDate);

        return DB::transaction(function () use ($template, $project, $overrides, $definitions, $baseDate, $data): Collection {
            $created = collect();
            $milestoneMap = [];

            foreach ($definitions as $index => $definition) {
                if (! is_array($definition)) {
                    continue;
                }

                $offsetDays = (int) ($definition['target_offset_days'] ?? 0);
                $targetDate = $baseDate->copy()->addDays($offsetDays);

                $override = $overrides['milestones'][$index] ?? [];

                $milestone = Milestone::query()->create([
                    'team_id' => $project->team_id,
                    'project_id' => $project->getKey(),
                    'owner_id' => $override['owner_id'] ?? $definition['owner_id'] ?? $project->creator_id,
                    'title' => $override['title'] ?? $definition['title'] ?? ('Milestone ' . ($index + 1)),
                    'description' => $definition['description'] ?? null,
                    'target_date' => ($override['target_date'] ?? $targetDate)->toDateString(),
                    'milestone_type' => $definition['milestone_type'] ?? \App\Enums\MilestoneType::PHASE_COMPLETION,
                    'priority_level' => $definition['priority_level'] ?? \App\Enums\MilestonePriority::MEDIUM,
                    'status' => MilestoneStatus::NOT_STARTED,
                    'completion_percentage' => 0,
                    'schedule_variance_days' => 0,
                    'is_critical' => (bool) ($definition['is_critical'] ?? false),
                    'is_at_risk' => false,
                    'requires_approval' => (bool) ($definition['requires_approval'] ?? false),
                ]);

                $milestoneMap[$index] = $milestone;
                $created->push($milestone);

                $deliverables = $definition['deliverables'] ?? [];
                if (is_array($deliverables)) {
                    foreach ($deliverables as $deliverableDefinition) {
                        if (! is_array($deliverableDefinition)) {
                            continue;
                        }

                        $dueOffsetDays = (int) ($deliverableDefinition['due_offset_days'] ?? $offsetDays);
                        $dueDate = $baseDate->copy()->addDays($dueOffsetDays);

                        $milestone->deliverables()->create([
                            'name' => $deliverableDefinition['name'] ?? 'Deliverable',
                            'description' => $deliverableDefinition['description'] ?? null,
                            'owner_id' => $deliverableDefinition['owner_id'] ?? $milestone->owner_id,
                            'due_date' => $dueDate->toDateString(),
                            'acceptance_criteria' => $deliverableDefinition['acceptance_criteria'] ?? null,
                            'status' => \App\Enums\DeliverableStatus::PENDING,
                            'requires_approval' => (bool) ($deliverableDefinition['requires_approval'] ?? false),
                        ]);
                    }
                }
            }

            $dependencyDefinitions = $data['dependencies'] ?? [];

            if (is_array($dependencyDefinitions)) {
                foreach ($dependencyDefinitions as $definition) {
                    if (! is_array($definition)) {
                        continue;
                    }

                    $preIndex = $definition['predecessor_index'] ?? null;
                    $sucIndex = $definition['successor_index'] ?? null;

                    if (! is_int($preIndex) || ! is_int($sucIndex)) {
                        continue;
                    }

                    if (! isset($milestoneMap[$preIndex], $milestoneMap[$sucIndex])) {
                        continue;
                    }

                    $this->dependencies->createDependency(
                        $milestoneMap[$preIndex],
                        $milestoneMap[$sucIndex],
                        \App\Enums\DependencyType::from((string) ($definition['dependency_type'] ?? \App\Enums\DependencyType::FINISH_TO_START->value)),
                        (int) ($definition['lag_days'] ?? 0),
                    );
                }
            }

            $template->increment('usage_count');

            return $created;
        });
    }

    /**
     * @return Collection<int, Milestone>
     */
    public function calculateCriticalPath(Project $project): Collection
    {
        return Milestone::query()
            ->where('project_id', $project->getKey())
            ->where('is_critical', true)
            ->orderBy('target_date')
            ->get();
    }

    private function assertOwnerHasProjectAccess(Project $project, User $owner): void
    {
        $hasAccess = $project->teamMembers()->whereKey($owner->getKey())->exists();

        if (! $hasAccess) {
            throw new \DomainException(__('app.messages.milestone_owner_requires_project_access'));
        }
    }

    private function notifyAssignment(Milestone $milestone): void
    {
        if (! $milestone->owner instanceof User) {
            return;
        }

        $this->notifications->sendActivityAlert(
            $milestone->owner,
            __('notifications.milestones.assigned_title'),
            __('notifications.milestones.assigned_body', [
                'title' => $milestone->title,
                'date' => $milestone->target_date->toDateString(),
            ]),
        );
    }
}

