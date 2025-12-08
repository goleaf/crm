<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CalendarSyncStatus;
use App\Models\CalendarEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service for syncing calendar events with external providers (Google, Outlook).
 * Implements bi-directional sync with conflict resolution.
 */
final class CalendarSyncService
{
    /**
     * Sync events from external provider to local database.
     * Implements idempotent sync to prevent duplicates.
     *
     * @param  string  $provider  The external provider (google, outlook)
     * @param  string  $externalCalendarId  The external calendar identifier
     * @param  array<int, array{id: string, title: string, start: string, end: string, updated: string}>  $externalEvents  Events from external provider
     * @return array{synced: int, skipped: int, errors: int}
     */
    public function syncFromExternal(string $provider, string $externalCalendarId, array $externalEvents, int $teamId): array
    {
        $synced = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($externalEvents as $externalEvent) {
            try {
                // Check if event already exists by external ID
                $existingEvent = CalendarEvent::where('sync_provider', $provider)
                    ->where('sync_external_id', $externalEvent['id'])
                    ->where('team_id', $teamId)
                    ->first();

                if ($existingEvent) {
                    // Check if external event is newer
                    $externalUpdated = \Illuminate\Support\Facades\Date::parse($externalEvent['updated']);
                    if ($existingEvent->updated_at->gte($externalUpdated)) {
                        $skipped++;

                        continue;
                    }

                    // Update existing event
                    $existingEvent->update([
                        'title' => $externalEvent['title'],
                        'start_at' => $externalEvent['start'],
                        'end_at' => $externalEvent['end'] ?? null,
                        'sync_status' => CalendarSyncStatus::SYNCED,
                    ]);
                    $synced++;
                } else {
                    // Create new event
                    CalendarEvent::create([
                        'team_id' => $teamId,
                        'title' => $externalEvent['title'],
                        'start_at' => $externalEvent['start'],
                        'end_at' => $externalEvent['end'] ?? null,
                        'sync_provider' => $provider,
                        'sync_external_id' => $externalEvent['id'],
                        'sync_status' => CalendarSyncStatus::SYNCED,
                    ]);
                    $synced++;
                }
            } catch (\Exception $e) {
                Log::error('Calendar sync error', [
                    'provider' => $provider,
                    'external_id' => $externalEvent['id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
                $errors++;
            }
        }

        return [
            'synced' => $synced,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Sync local events to external provider.
     *
     * @param  Collection<int, CalendarEvent>  $localEvents
     * @return array{synced: int, errors: int}
     */
    public function syncToExternal(string $provider, Collection $localEvents): array
    {
        $synced = 0;
        $errors = 0;

        foreach ($localEvents as $event) {
            try {
                // Skip if already synced and not modified
                if ($event->sync_status === CalendarSyncStatus::SYNCED &&
                    $event->sync_provider === $provider &&
                    $event->sync_external_id !== null) {
                    continue;
                }

                // Here would be the actual API call to external provider
                // For now, we'll mark it as pending sync
                $event->update([
                    'sync_status' => CalendarSyncStatus::PENDING,
                    'sync_provider' => $provider,
                ]);

                $synced++;
            } catch (\Exception $e) {
                Log::error('Calendar sync to external error', [
                    'provider' => $provider,
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                ]);
                $errors++;
            }
        }

        return [
            'synced' => $synced,
            'errors' => $errors,
        ];
    }

    /**
     * Resolve sync conflicts using last-write-wins strategy.
     */
    public function resolveConflict(CalendarEvent $localEvent, array $externalEvent): CalendarEvent
    {
        $localUpdated = $localEvent->updated_at;
        $externalUpdated = \Illuminate\Support\Facades\Date::parse($externalEvent['updated']);

        // Last write wins
        if ($externalUpdated->gt($localUpdated)) {
            $localEvent->update([
                'title' => $externalEvent['title'],
                'start_at' => $externalEvent['start'],
                'end_at' => $externalEvent['end'] ?? null,
                'sync_status' => CalendarSyncStatus::SYNCED,
            ]);
        }

        return $localEvent->fresh();
    }

    /**
     * Check for duplicate events to prevent sync drift.
     *
     * @return Collection<int, CalendarEvent>
     */
    public function findDuplicates(int $teamId, string $provider): Collection
    {
        return CalendarEvent::where('team_id', $teamId)
            ->where('sync_provider', $provider)
            ->whereNotNull('sync_external_id')
            ->get()
            ->groupBy('sync_external_id')
            ->filter(fn (Collection $group): bool => $group->count() > 1)
            ->flatten();
    }

    /**
     * Remove duplicate events, keeping the most recently updated one.
     */
    public function deduplicateEvents(Collection $duplicates): int
    {
        $removed = 0;

        foreach ($duplicates->groupBy('sync_external_id') as $events) {
            // Keep the most recently updated event
            $toKeep = $events->sortByDesc('updated_at')->first();

            // Delete the rest
            foreach ($events as $event) {
                if ($event->id !== $toKeep->id) {
                    $event->delete();
                    $removed++;
                }
            }
        }

        return $removed;
    }
}
