<?php

declare(strict_types=1);

namespace App\Services\Notes;

use App\Enums\NoteHistoryEvent;
use App\Models\Note;

final class NoteHistoryService
{
    public function record(Note $note, NoteHistoryEvent $event = NoteHistoryEvent::UPDATED): void
    {
        $note->loadMissing('customFieldValues.customField');

        $snapshot = [
            'title' => $note->title,
            'category' => $note->category,
            'visibility' => $note->visibility->value ?? null,
            'body' => $note->plainBody(),
            'event' => $event,
            'team_id' => $note->team_id,
            'user_id' => auth()->id() ?? $note->creator_id,
        ];

        $last = $note->histories()->first();

        if ($last !== null && $event === NoteHistoryEvent::UPDATED && $last->matchesSnapshot($snapshot)) {
            return;
        }

        $note->histories()->create($snapshot);
    }
}
