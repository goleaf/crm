<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Activity;
use App\Models\Model;
use App\Services\Tenancy\CurrentTeamResolver;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    /**
     * @return MorphMany<Activity, $this>
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject')->latest();
    }

    protected static function bootLogsActivity(): void
    {
        static::created(function (Model $model): void {
            $model->recordActivity('created');
        });

        static::updated(function (Model $model): void {
            $changes = $model->getActivityChanges();

            if ($changes !== []) {
                $model->recordActivity('updated', $changes);
            }
        });

        static::deleted(function (Model $model): void {
            $model->recordActivity('deleted');
        });
    }

    /**
     * @param array<string, mixed> $changes
     */
    protected function recordActivity(string $event, array $changes = []): void
    {
        $teamId = method_exists($this, 'getAttribute') ? $this->getAttribute('team_id') : null;
        $teamId ??= CurrentTeamResolver::resolveId(Auth::user());

        $this->activities()->create([
            'team_id' => $teamId,
            'event' => $event,
            'causer_id' => Auth::id(),
            'changes' => $changes === [] ? null : $changes,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getActivityChanges(): array
    {
        $changes = Arr::except($this->getChanges(), ['updated_at']);

        if ($changes === []) {
            return [];
        }

        return [
            'attributes' => $changes,
            'old' => Arr::only($this->getOriginal(), array_keys($changes)),
        ];
    }
}
