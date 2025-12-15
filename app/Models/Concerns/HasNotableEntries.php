<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\NotableEntry;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, NotableEntry> $notables
 */
trait HasNotableEntries
{
    /**
     * @return MorphMany<NotableEntry, $this>
     */
    public function notables(): MorphMany
    {
        return $this->morphMany(NotableEntry::class, 'notable')
            ->orderBy(config('notable.order_by_column', 'created_at'), config('notable.order_by_direction', 'desc'));
    }

    public function addNotableNote(string $note, ?EloquentModel $creator = null): NotableEntry
    {
        $attributes = ['note' => $note];

        if ($creator instanceof EloquentModel) {
            $attributes['creator_type'] = $creator->getMorphClass();
            $attributes['creator_id'] = $creator->getKey();
        }

        $teamColumn = config('notable.team_column', 'team_id');
        $teamId = $this->getAttribute($teamColumn);

        if ($teamId !== null) {
            $attributes[$teamColumn] = $teamId;
        }

        return $this->notables()->create($attributes);
    }

    /**
     * @return EloquentCollection<int, NotableEntry>
     */
    public function notableNotes(): EloquentCollection
    {
        return $this->notables()->get();
    }

    public function latestNotableNote(): ?NotableEntry
    {
        return $this->notables()->first();
    }

    public function hasNotableNotes(): bool
    {
        return $this->notables()->exists();
    }

    public function notableNotesCount(): int
    {
        return $this->notables()->count();
    }

    /**
     * @return EloquentCollection<int, NotableEntry>
     */
    public function notableNotesByCreator(EloquentModel $creator): EloquentCollection
    {
        return $this->notables()
            ->where('creator_type', $creator->getMorphClass())
            ->where('creator_id', $creator->getKey())
            ->get();
    }

    /**
     * @return EloquentCollection<int, NotableEntry>
     */
    public function notableNotesWithCreator(): EloquentCollection
    {
        return $this->notables()->with('creator')->get();
    }

    public function deleteNotableNote(int $noteId): bool
    {
        return $this->notables()->whereKey($noteId)->delete() > 0;
    }

    public function updateNotableNote(int $noteId, string $note): bool
    {
        return $this->notables()->whereKey($noteId)->update(['note' => $note]) > 0;
    }

    /**
     * @return EloquentCollection<int, NotableEntry>
     */
    public function searchNotableNotes(string $searchTerm): EloquentCollection
    {
        return $this->notables()->search($searchTerm)->get();
    }

    /**
     * @return EloquentCollection<int, NotableEntry>
     */
    public function notableNotesToday(): EloquentCollection
    {
        return $this->notables()->today()->get();
    }

    /**
     * @return EloquentCollection<int, NotableEntry>
     */
    public function notableNotesThisWeek(): EloquentCollection
    {
        $end = now();
        $start = $end->copy()->subDays(7);

        return $this->notables()
            ->whereBetween('created_at', [$start, $end])
            ->get();
    }

    /**
     * @return EloquentCollection<int, NotableEntry>
     */
    public function notableNotesThisMonth(): EloquentCollection
    {
        return $this->notables()->thisMonth()->get();
    }

    /**
     * @param \DateTimeInterface|string $startDate
     * @param \DateTimeInterface|string $endDate
     *
     * @return EloquentCollection<int, NotableEntry>
     */
    public function notableNotesInRange($startDate, $endDate): EloquentCollection
    {
        return $this->notables()->betweenDates($startDate, $endDate)->get();
    }
}
