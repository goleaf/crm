<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;

final class NotePrintController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Note $note): View
    {
        $this->authorize('view', $note);

        $note->load([
            'companies',
            'people',
            'opportunities',
            'cases',
            'tasks',
            'creator',
            'attachments',
            'customFieldValues.customField',
        ]);

        return view('notes.print', [
            'note' => $note,
            'body' => $note->body(),
        ]);
    }
}
