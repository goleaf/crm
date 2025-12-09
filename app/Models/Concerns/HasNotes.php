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
        $this->notes()->attach($note);
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
        $noteIds = collect($notes)->map(fn ($note) => $note instanceof Note ? $note->id : $note)->all();
        $this->notes()->sync($noteIds);
    }
}
