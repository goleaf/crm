<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\NoteHistoryEvent;
use App\Models\Note;
use App\Models\User;
use App\Services\Notes\NoteHistoryService;
use Illuminate\Support\Facades\DB;

final readonly class NoteObserver
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
        DB::afterCommit(function () use ($note): void {
            $freshNote = $note->fresh(['customFieldValues.customField', 'team']);

            if ($freshNote === null) {
                return;
            }

            app(NoteHistoryService::class)->record(
                $freshNote,
                $note->wasRecentlyCreated ? NoteHistoryEvent::CREATED : NoteHistoryEvent::UPDATED
            );
        });
    }

    public function deleted(Note $note): void
    {
        DB::afterCommit(function () use ($note): void {
            app(NoteHistoryService::class)->record($note, NoteHistoryEvent::DELETED);
        });
    }
}
