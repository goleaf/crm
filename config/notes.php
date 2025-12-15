<?php

declare(strict_types=1);

use App\Enums\NoteCategory;
use App\Enums\NoteVisibility;

return [
    'templates' => [
        [
            'key' => 'meeting_recap',
            'label' => 'Meeting recap',
            'title' => 'Meeting recap',
            'category' => NoteCategory::MEETING->value,
            'visibility' => NoteVisibility::INTERNAL->value,
            'body' => '<p>Summary of discussion:</p><ul><li>Key decisions:</li><li>Risks or blockers:</li><li>Owners:</li></ul><p>Next steps and due dates:</p>',
        ],
        [
            'key' => 'call_log',
            'label' => 'Call log',
            'title' => 'Call notes',
            'category' => NoteCategory::CALL->value,
            'visibility' => NoteVisibility::INTERNAL->value,
            'body' => '<p>Call purpose:</p><p>Participants:</p><p>Highlights:</p><p>Action items:</p>',
        ],
        [
            'key' => 'customer_update',
            'label' => 'Customer update',
            'title' => 'Customer update',
            'category' => NoteCategory::FOLLOW_UP->value,
            'visibility' => NoteVisibility::EXTERNAL->value,
            'body' => '<p>Summary for customer:</p><ul><li>Context:</li><li>Progress:</li><li>Questions:</li></ul><p>Next steps:</p>',
        ],
    ],
];
