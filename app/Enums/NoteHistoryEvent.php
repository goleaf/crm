<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum NoteHistoryEvent: string implements HasLabel
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case DELETED = 'deleted';

    public function getLabel(): string
    {
        return match ($this) {
            self::CREATED => __('enums.note_history_event.created'),
            self::UPDATED => __('enums.note_history_event.updated'),
            self::DELETED => __('enums.note_history_event.deleted'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
