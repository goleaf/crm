<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TerritoryRole;
use App\Models\Territory;
use App\Models\TerritoryAssignment;
use App\Models\TerritoryOverlap;
use App\Models\TerritoryRecord;
use App\Models\TerritoryTransfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class TerritoryService
{
    /**
     * Assign a record to a territory based on assignment rules
     */
    public function assignRecord(Model $record, ?Territory $territory = null, bool $isPrimary = true, ?string $reason = null): ?TerritoryRecord
    {
        // If no territory specified, find matching territory
        if (! $territory instanceof \App\Models\Territory) {
            $territory = $this->findMatchingTerritory($record);
        }

        if (! $territory instanceof \App\Models\Territory) {
            return null;
        }

        // Check for overlaps
        $overlaps = $this->checkOverlaps($territory, $record);
        if ($overlaps->isNotEmpty()) {
            $territory = $this->resolveOverlap($overlaps, $record);
        }

        // Create or update territory record
        return TerritoryRecord::updateOrCreate(
            [
                'territory_id' => $territory->id,
                'record_type' => $record::class,
                'record_id' => $record->id,
            ],
            [
                'is_primary' => $isPrimary,
                'assigned_at' => now(),
                'assignment_reason' => $reason ?? 'Auto-assigned by rules',
            ],
        );
    }

    /**
     * Find a territory that matches the record's attributes
     */
    public function findMatchingTerritory(Model $record): ?Territory
    {
        $territories = Territory::where('is_active', true)
            ->whereNotNull('assignment_rules')
            ->get();

        foreach ($territories as $territory) {
            if ($territory->matchesAssignmentRules($record)) {
                return $territory;
            }
        }

        return null;
    }

    /**
     * Check if there are overlapping territories for a record
     */
    public function checkOverlaps(Territory $territory, Model $record): Collection
    {
        $overlappingTerritories = collect();

        // Check all overlaps where this territory is involved
        $overlaps = TerritoryOverlap::where('territory_a_id', $territory->id)
            ->orWhere('territory_b_id', $territory->id)
            ->get();

        foreach ($overlaps as $overlap) {
            $otherTerritory = $overlap->territory_a_id === $territory->id
                ? $overlap->territoryB
                : $overlap->territoryA;

            if ($otherTerritory->matchesAssignmentRules($record)) {
                $overlappingTerritories->push($overlap);
            }
        }

        return $overlappingTerritories;
    }

    /**
     * Resolve territory overlap based on resolution strategy
     */
    public function resolveOverlap(Collection $overlaps, Model $record): Territory
    {
        $overlap = $overlaps->first();

        return match ($overlap->resolution_strategy->value) {
            'priority' => $overlap->priorityTerritory ?? $overlap->territoryA,
            'split' => $this->splitAssignment($overlap, $record),
            default => $overlap->territoryA, // Manual - default to first
        };
    }

    /**
     * Split assignment between overlapping territories
     */
    private function splitAssignment(TerritoryOverlap $overlap, Model $record): Territory
    {
        // Simple round-robin split based on record ID
        return $record->id % 2 === 0 ? $overlap->territoryA : $overlap->territoryB;
    }

    /**
     * Transfer a record from one territory to another
     */
    public function transferRecord(
        Model $record,
        Territory $fromTerritory,
        Territory $toTerritory,
        User $initiator,
        ?string $reason = null,
    ): TerritoryTransfer {
        return DB::transaction(function () use ($record, $fromTerritory, $toTerritory, $initiator, $reason) {
            // Update the territory record
            TerritoryRecord::where('record_type', $record::class)
                ->where('record_id', $record->id)
                ->where('territory_id', $fromTerritory->id)
                ->update(['territory_id' => $toTerritory->id]);

            // Create transfer record
            return TerritoryTransfer::create([
                'from_territory_id' => $fromTerritory->id,
                'to_territory_id' => $toTerritory->id,
                'record_type' => $record::class,
                'record_id' => $record->id,
                'initiated_by' => $initiator->id,
                'reason' => $reason,
                'transferred_at' => now(),
            ]);
        });
    }

    /**
     * Check if a user has access to a record based on territory assignments
     */
    public function userHasAccess(User $user, Model $record, ?TerritoryRole $minimumRole = null): bool
    {
        // Get the record's territories
        $recordTerritories = TerritoryRecord::where('record_type', $record::class)
            ->where('record_id', $record->id)
            ->pluck('territory_id');

        if ($recordTerritories->isEmpty()) {
            return false;
        }

        // Get user's territory assignments
        $userAssignments = TerritoryAssignment::where('user_id', $user->id)
            ->whereIn('territory_id', $recordTerritories)
            ->get();

        if ($userAssignments->isEmpty()) {
            return false;
        }

        // Check role if specified
        if ($minimumRole instanceof \App\Enums\TerritoryRole) {
            return $userAssignments->contains(fn ($assignment): bool => $this->roleHasPermission($assignment->role, $minimumRole));
        }

        return true;
    }

    /**
     * Check if a role has sufficient permissions
     */
    private function roleHasPermission(TerritoryRole $userRole, TerritoryRole $requiredRole): bool
    {
        $hierarchy = [
            TerritoryRole::VIEWER->value => 1,
            TerritoryRole::MEMBER->value => 2,
            TerritoryRole::OWNER->value => 3,
        ];

        return ($hierarchy[$userRole->value] ?? 0) >= ($hierarchy[$requiredRole->value] ?? 0);
    }

    /**
     * Get all records accessible to a user through their territory assignments
     */
    public function getAccessibleRecords(User $user, string $recordType): Collection
    {
        $territoryIds = TerritoryAssignment::where('user_id', $user->id)
            ->pluck('territory_id');

        if ($territoryIds->isEmpty()) {
            return collect();
        }

        return TerritoryRecord::where('record_type', $recordType)
            ->whereIn('territory_id', $territoryIds)
            ->with('record')
            ->get()
            ->pluck('record');
    }

    /**
     * Balance territories by redistributing records
     */
    public function balanceTerritories(Collection $territories): array
    {
        $stats = [];

        foreach ($territories as $territory) {
            $recordCount = TerritoryRecord::where('territory_id', $territory->id)->count();
            $stats[$territory->id] = $recordCount;
        }

        $average = array_sum($stats) / count($stats);
        $balanced = [];

        foreach ($stats as $territoryId => $count) {
            $balanced[$territoryId] = [
                'current' => $count,
                'target' => (int) round($average),
                'difference' => $count - (int) round($average),
            ];
        }

        return $balanced;
    }

    /**
     * Get territory hierarchy as a tree structure
     */
    public function getTerritoryTree(?int $parentId = null): Collection
    {
        return Territory::where('parent_id', $parentId)
            ->where('is_active', true)
            ->with(['children' => function ($query): void {
                $query->where('is_active', true);
            }])
            ->get();
    }

    /**
     * Assign a user to a territory
     */
    public function assignUser(
        Territory $territory,
        User $user,
        TerritoryRole $role = TerritoryRole::MEMBER,
        bool $isPrimary = false,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
    ): TerritoryAssignment {
        return TerritoryAssignment::create([
            'territory_id' => $territory->id,
            'user_id' => $user->id,
            'role' => $role,
            'is_primary' => $isPrimary,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }
}
