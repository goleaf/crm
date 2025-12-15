<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Note;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Trait HasNotes
 *
 * Add notes functionality to any Eloquent model.
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Note> $notes
 * @property-read int|null $notes_count
 */
trait HasNotes
{
    /**
     * Get all notes for this model.
     *
     * @return MorphToMany<Note, $this>
     */
    public function notes(): MorphToMany
    {
        return $this->morphToMany(Note::class, 'noteable')
            ->withTimestamps()
            ->orderByDesc('noteables.created_at');
    }

    /**
     * Add a note to this model.
     */
    public function addNote(Note $note): void
    {
        $createdAt = $note->created_at ?? now();
        $updatedAt = $note->updated_at ?? $createdAt;

        $this->notes()->syncWithoutDetaching([
            $note->getKey() => [
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ],
        ]);
    }

    /**
     * Remove a note from this model.
     */
    public function removeNote(Note $note): void
    {
        $this->notes()->detach($note);
    }

    /**
     * Check if this model has a specific note.
     */
    public function hasNote(Note $note): bool
    {
        return $this->notes()->where('note_id', $note->id)->exists();
    }

    /**
     * Sync notes for this model.
     *
     * @param array<int>|Note[] $notes
     */
    public function syncNotes(array $notes): void
    {
        $noteModels = collect($notes)->filter(fn (mixed $note): bool => $note instanceof Note);

        $noteIds = collect($notes)
            ->map(fn (mixed $note): ?int => $note instanceof Note ? $note->getKey() : (is_int($note) ? $note : null))
            ->filter()
            ->unique()
            ->values();

        $knownIds = $noteModels
            ->map(fn (Note $note): ?int => $note->getKey())
            ->filter()
            ->unique();

        $missingIds = $noteIds->diff($knownIds)->values();

        $missingNotes = $missingIds->isEmpty()
            ? collect()
            : Note::withoutGlobalScopes()->whereIn('id', $missingIds)->get();

        /** @var \Illuminate\Support\Collection<int, Note> $allNotes */
        $allNotes = $noteModels
            ->merge($missingNotes)
            ->unique(fn (Note $note): ?int => $note->getKey())
            ->values();

        $pivotData = $allNotes->mapWithKeys(function (Note $note): array {
            $createdAt = $note->created_at ?? now();
            $updatedAt = $note->updated_at ?? $createdAt;

            return [
                $note->getKey() => [
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ],
            ];
        })->all();

        $this->notes()->sync($pivotData);
    }
}
