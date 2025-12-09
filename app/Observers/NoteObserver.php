<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\NoteHistoryEvent;
use App\Models\Note;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\Notes\NoteHistoryService;

final class NoteObserver
{
    public function creating(Note $note): void
    {
        if (auth()->check()) {
            /** @var User $user */
            $user = auth()->user();
            $note->creator_id = $user->getKey();
            $note->team_id = $user->currentTeam->getKey();
        }
    }

    public function saved(Note $note): void
    {
        $freshNote = $note->fresh(['customFieldValues.customField', 'team']);

        if ($freshNote === null) {
            return;
        }

        resolve(NoteHistoryService::class)->record(
            $freshNote,
            $note->wasRecentlyCreated ? NoteHistoryEvent::CREATED : NoteHistoryEvent::UPDATED,
        );

        // Log activity
        resolve(ActivityService::class)->log(
            $freshNote,
            $note->wasRecentlyCreated ? 'created' : 'updated',
            [
                'title' => $freshNote->title,
                'category' => $freshNote->category,
                'visibility' => $freshNote->visibility->value,
            ],
        );
    }

    public function deleted(Note $note): void
    {
        resolve(NoteHistoryService::class)->record($note, NoteHistoryEvent::DELETED);

        // Log activity
        resolve(ActivityService::class)->log(
            $note,
            'deleted',
            ['title' => $note->title],
        );
    }
}
