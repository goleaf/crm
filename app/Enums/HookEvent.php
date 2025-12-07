<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum HookEvent: string implements HasLabel
{
    case BEFORE_SAVE = 'before_save';
    case AFTER_SAVE = 'after_save';
    case BEFORE_DELETE = 'before_delete';
    case AFTER_DELETE = 'after_delete';
    case BEFORE_RETRIEVE = 'before_retrieve';
    case AFTER_RETRIEVE = 'after_retrieve';
    case BEFORE_RELATIONSHIP = 'before_relationship';
    case AFTER_RELATIONSHIP = 'after_relationship';
    case PROCESS_RECORD = 'process_record';

    public function getLabel(): string
    {
        return match ($this) {
            self::BEFORE_SAVE => __('enums.hook_event.before_save'),
            self::AFTER_SAVE => __('enums.hook_event.after_save'),
            self::BEFORE_DELETE => __('enums.hook_event.before_delete'),
            self::AFTER_DELETE => __('enums.hook_event.after_delete'),
            self::BEFORE_RETRIEVE => __('enums.hook_event.before_retrieve'),
            self::AFTER_RETRIEVE => __('enums.hook_event.after_retrieve'),
            self::BEFORE_RELATIONSHIP => __('enums.hook_event.before_relationship'),
            self::AFTER_RELATIONSHIP => __('enums.hook_event.after_relationship'),
            self::PROCESS_RECORD => __('enums.hook_event.process_record'),
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
