<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeadAssignmentStrategy;
use App\Models\Lead;
use App\Models\Territory;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class LeadAssignmentService
{
    public function __construct(
        private TerritoryService $territoryService
    ) {}

    /**
     * Assign a lead based on the configured strategy
     */
    public function assign(Lead $lead, ?LeadAssignmentStrategy $strategy = null): ?User
    {
        $strategy ??= $lead->assignment_strategy ?? LeadAssignmentStrategy::MANUAL;

        return DB::transaction(function () use ($lead, $strategy): ?User {
            $assignedUser = match ($strategy) {
                LeadAssignmentStrategy::ROUND_ROBIN => $this->assignRoundRobin($lead),
                LeadAssignmentStrategy::TERRITORY => $this->assignByTerritory($lead),
                LeadAssignmentStrategy::WEIGHTED => $this->assignWeighted($lead),
                LeadAssignmentStrategy::RULE_BASED => $this->assignRuleBased($lead),
                LeadAssignmentStrategy::MANUAL => null,
            };

            if ($assignedUser instanceof User) {
                $lead->forceFill([
                    'assigned_to_id' => $assignedUser->id,
                    'assignment_strategy' => $strategy,
                ])->save();

                $this->logAssignment($lead, $assignedUser, $strategy);
            }

            return $assignedUser;
        });
    }

    /**
     * Assign lead using round-robin strategy
     */
    private function assignRoundRobin(Lead $lead): ?User
    {
        $eligibleUsers = $this->getEligibleUsers($lead);

        if ($eligibleUsers->isEmpty()) {
            return null;
        }

        // Get the last assigned user index for this team
        $cacheKey = "lead_round_robin:{$lead->team_id}";
        $lastIndex = Cache::get($cacheKey, -1);

        // Calculate next index in round-robin sequence
        $nextIndex = ($lastIndex + 1) % $eligibleUsers->count();
        $assignedUser = $eligibleUsers->get($nextIndex);

        // Update cache with new index
        Cache::put($cacheKey, $nextIndex, now()->addDay());

        return $assignedUser;
    }

    /**
     * Assign lead based on territory rules
     */
    private function assignByTerritory(Lead $lead): ?User
    {
        $territory = $this->territoryService->findMatchingTerritory($lead);

        if (! $territory instanceof Territory) {
            return null;
        }

        // Assign the territory record
        $this->territoryService->assignRecord($lead, $territory, true, 'Auto-assigned by territory rules');

        // Get the primary owner of the territory
        $territoryAssignment = $territory->assignments()
            ->where('is_primary', true)
            ->first();

        if ($territoryAssignment === null) {
            // Fall back to any territory member
            $territoryAssignment = $territory->assignments()->first();
        }

        return $territoryAssignment?->user;
    }

    /**
     * Assign lead using weighted distribution
     */
    private function assignWeighted(Lead $lead): ?User
    {
        $eligibleUsers = $this->getEligibleUsers($lead);

        if ($eligibleUsers->isEmpty()) {
            return null;
        }

        // Calculate current load for each user
        $userLoads = $eligibleUsers->mapWithKeys(function (User $user): array {
            $activeLeads = Lead::where('assigned_to_id', $user->id)
                ->whereNull('converted_at')
                ->count();

            return [$user->id => $activeLeads];
        });

        // Assign to user with lowest load
        $minLoad = $userLoads->min();
        $userId = $userLoads->filter(fn (int $load): bool => $load === $minLoad)->keys()->first();

        return $eligibleUsers->firstWhere('id', $userId);
    }

    /**
     * Assign lead using rule-based logic
     */
    private function assignRuleBased(Lead $lead): ?User
    {
        // Try territory first
        $user = $this->assignByTerritory($lead);

        if ($user instanceof User) {
            return $user;
        }

        // Fall back to weighted distribution
        return $this->assignWeighted($lead);
    }

    /**
     * Get eligible users for assignment within the lead's team
     */
    private function getEligibleUsers(Lead $lead): Collection
    {
        if ($lead->team_id === null) {
            return collect();
        }

        // Get all active users in the team
        return User::whereHas('teams', function ($query) use ($lead): void {
            $query->where('teams.id', $lead->team_id);
        })
            ->where('email_verified_at', '!=', null)
            ->orderBy('id')
            ->get();
    }

    /**
     * Log assignment for audit trail
     */
    private function logAssignment(Lead $lead, User $user, LeadAssignmentStrategy $strategy): void
    {
        Log::info('Lead assigned', [
            'lead_id' => $lead->id,
            'lead_name' => $lead->name,
            'assigned_to' => $user->name,
            'assigned_to_id' => $user->id,
            'strategy' => $strategy->value,
            'team_id' => $lead->team_id,
        ]);
    }

    /**
     * Bulk assign multiple leads
     */
    public function bulkAssign(Collection $leads, LeadAssignmentStrategy $strategy): Collection
    {
        return $leads->map(fn (Lead $lead): array => [
            'lead' => $lead,
            'user' => $this->assign($lead, $strategy),
        ]);
    }

    /**
     * Reassign leads from one user to another
     */
    public function reassign(User $fromUser, User $toUser, ?int $teamId = null): int
    {
        $query = Lead::where('assigned_to_id', $fromUser->id)
            ->whereNull('converted_at');

        if ($teamId !== null) {
            $query->where('team_id', $teamId);
        }

        $count = $query->count();

        $query->update([
            'assigned_to_id' => $toUser->id,
        ]);

        Log::info('Leads reassigned', [
            'from_user' => $fromUser->name,
            'to_user' => $toUser->name,
            'count' => $count,
            'team_id' => $teamId,
        ]);

        return $count;
    }
}
