<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Activity;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class ActivityService
{
    /**
     * Log an activity for a model.
     *
     * @param  array<string, mixed>  $changes
     */
    public function log(
        Model $subject,
        string $event,
        ?array $changes = null,
        ?User $causer = null,
        ?Team $team = null
    ): Activity {
        // Resolve causer
        if (! $causer instanceof \App\Models\User && auth()->check()) {
            $causer = auth()->user();
        }

        // Resolve team
        if (! $team instanceof \App\Models\Team) {
            if (method_exists($subject, 'team') && $subject->team instanceof Team) {
                $team = $subject->team;
            } elseif ($causer instanceof User && $causer->currentTeam instanceof Team) {
                $team = $causer->currentTeam;
            }
        }

        if (! $team instanceof Team) {
            throw new \RuntimeException('Cannot log activity without a team context');
        }

        return Activity::create([
            'team_id' => $team->getKey(),
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'causer_id' => $causer?->getKey(),
            'event' => $event,
            'changes' => $changes,
        ]);
    }

    /**
     * Get activities for a model.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Activity>
     */
    public function getActivitiesFor(Model $subject): \Illuminate\Database\Eloquent\Collection
    {
        return Activity::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->with(['causer', 'team'])
            ->latest()
            ->get();
    }

    /**
     * Check if user can view activities for a model.
     */
    public function canViewActivities(Model $subject, User $user): bool
    {
        // Check if subject has team relationship
        if (method_exists($subject, 'team')) {
            $subjectTeam = $subject->team;
            if ($subjectTeam instanceof Team) {
                return $user->belongsToTeam($subjectTeam);
            }
        }

        // Check if subject has team_id attribute
        if (isset($subject->team_id)) {
            $team = Team::find($subject->team_id);
            if ($team instanceof Team) {
                return $user->belongsToTeam($team);
            }
        }

        return false;
    }
}
